<?php

namespace App\Processor;

use App\Exceptions\InvalidUrlException;
use Illuminate\Support\Facades\Http;
use App\Models\Webpage;

class WebCrawler
{
    public static function crawl(string $seedUrl, int $depth): array
    {

        if ($depth > 9) {
            return array("status" => false, "message" => "Depth is limited to values 9 or lower.");
        }
        $webpages = array();
        $urlPool = array();
        $scannedPages = array();
        $pageHashes = array();
        try {

            $seedPage = self::request($seedUrl, $pageHashes);
            $urlPool = array_merge($urlPool, $seedPage->getInternalUrls());
            array_push($scannedPages, $seedPage->getPageUrl());
            array_push($webpages, $seedPage->getResult());
            self::depthScanning($webpages, $urlPool, $scannedPages, $depth, $pageHashes);
            return $webpages;
        } catch (\App\Exceptions\InvalidUrlException $e) {
            return array("status" => false, "message" => $e->getMessage());
        } catch (\Throwable) {
            return array("status" => false, "message" => "An unexpected error occurred.");
        }
    }
    private static function depthScanning(&$webpages, &$urlPool, &$scannedPages, $depth, &$pageHashes)
    {
        while ($depth > 0) {
            $urls = count($urlPool);
            for ($i = 0; $i < $urls; $i++) {
                if (in_array($urlPool[$i], $scannedPages) || $urlPool[$i][0] === "#") {
                    continue;
                } else {
                    try {
                        $page = self::request($urlPool[$i], $pageHashes);
                    } catch (InvalidUrlException) {
                        continue;
                    }
                    $urlPool = array_merge($urlPool, $page->getInternalUrls());
                    array_push($webpages, $page->getResult());
                    array_push($scannedPages, $page->getPageUrl());
                    self::depthScanning($webpages, $urlPool, $scannedPages, --$depth, $pageHashes);
                    break 2;
                }
            }
            // Ran our of URLs to scan without hitting depth limit
            return;
        }
        return $webpages;
    }

    private static function isValidUrl($url)
    {
        return (filter_var($url, FILTER_VALIDATE_URL) !== false);
    }

    private static function request(string|array $url, array &$pageHashes): WEbpage
    {
        if (!self::isValidUrl($url)) {
            throw new InvalidUrlException("${url} is not a valid URL.");
        }
        if (is_array($url)) {
            //Multiple URLs, request in concurrent mode? 
        } else {
            $startTime = microtime(true);
            $response = Http::get($url);
            $responseHash = hash('md5', $response->body());
            if (in_array($responseHash, $pageHashes)) {
                throw new InvalidUrlException('Invalid URL - page has already been scanned.');
            }
            array_push($pageHashes, $responseHash);
            $elapsedTime = microtime(true) - $startTime;
            $webpage = new Webpage($url);
            $webpage->setLoadTime(strval($elapsedTime));
            if ($response->ok() === true) {
                $webpage->setHttpResponse($response->status());
                if ($response->status() === 200) {
                    return self::parseWebsite($webpage, $response);
                } else {
                    return $webpage;
                }
            } else {
                return $webpage;
            }
        }
    }

    private static function checkForAttribute(\DOMNode $node, string $name): ?string
    {
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $index => $attribute) {
                if ($attribute->name === $name) {
                    return $attribute->value;
                }
            }
        }
        return null;
    }
    private static function parseWebsite(Webpage $webpage, object $request): Webpage
    {

        //Prevent throwing exceptions when you come across modern HTML tags
        libxml_use_internal_errors(true);
        $startTime = microtime(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($request->body());

        $parsedUrl = parse_url($webpage->getPageUrl());

        $nodes = $dom->getElementsByTagName('*');
        foreach ($nodes as $node) {
            $webpage->addNodeScanned();
            switch ($node->nodeName) {
                case 'title':
                    $webpage->setPageTitle($node->textContent);
                    break;
                case 'img':
                    $image = self::checkForAttribute($node, 'data-src');
                    if ($image !== null) {
                        $webpage->addImage($image);
                    } else {
                        $image = self::checkForAttribute($node, 'src');
                        if ($image !== null) {
                            $webpage->addImage($image);
                        }
                    }
                    break;
                case 'svg':
                    $webpage->addImage(htmlspecialchars($dom->saveHTML($node)));
                    break;
                case 'a':
                    $url = trim(self::checkForAttribute($node, 'href'));
                    if ($url != "") {
                        if ($url[0] === "/") {
                            $url = "{$parsedUrl['scheme']}://{$parsedUrl['host']}$url";
                            $webpage->addInternalUrl($url);
                        } elseif ($url[0] === "#" || parse_url($url, PHP_URL_HOST) === parse_url($webpage->getPageUrl(), PHP_URL_HOST)) {
                            $webpage->addInternalUrl($url);
                        } else {
                            $webpage->addExternalUrl($url);
                        }
                    }
                default:
                    $children = $node->childNodes->length;
                    for ($i = 0; $i < $children; $i++) {
                        if ($node->childNodes->item($i)->nodeType === 3) {
                            $text = $node->childNodes->item($i)->textContent;
                            $text = str_replace(array(',', '.', '!', '?', ' / '), '', $text);
                            $text = preg_replace('/\s+/', ' ', $text);
                            $webpage->addWordCount(count(array_filter(explode(' ', $text))));
                        }
                    }
                    break;
            }
        }
        $elapsedTime = microtime(true) - $startTime;
        $webpage->setParseTime(strval($elapsedTime));
        return $webpage;
    }
};
