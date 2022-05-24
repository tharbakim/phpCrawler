<?php

namespace App\Processor;

use App\Exceptions\InvalidUrlException;
use Illuminate\Support\Facades\Http;
use App\Models\Webpage;
use DOMNode;

/**
 * Static web crawling procedures to generate an array of \App\Models\Webpage objects representing the
 * calculated results of analysing a webpage.
 * @package App\Processor
 */
class WebCrawler
{
    /**
     * The entry point for performing a "crawl" and analysis of one or more URLs.
     * @param string $seedUrl The initial URL to scan and analyse
     * @param int $depth The maximum number of additional pages to crawl, based on links contained in $seedUrl that refer to the same host.
     * @return Webpage[]
     */
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

    /**
     * Recursive procedure for scanning additional pages once the seed URL has been processed.
     * @param mixed $webpages array of result values from scanned webpages
     * @param mixed $urlPool array of URLs that have been found while scanning previous pages, and are therefore candidates for additional scans
     * @param mixed $scannedPages array of URLs that have already been scanned
     * @param mixed $depth Number of pages yet to be scanned before reaching the maximum depth value
     * @param mixed $pageHashes Array of hashes calculated based on the body of the response for a URL. This is used to avoid scanning a single page multiple times where there are multiple valid URLs pointing to it.
     * @return mixed 
     */
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

    /**
     * Function for determining if a URL is valid.
     * @param mixed $url URL to check the validity of
     * @return bool 
     */
    private static function isValidUrl($url)
    {
        return (filter_var($url, FILTER_VALIDATE_URL) !== false);
    }

    /**
     * Performs the network request and validates that the page is both a valid webpage, and has not already been encountered based on a hash of the response.
     * @param string|array $url The URL to scan
     * @param array $pageHashes AN array of previously-encountered hashes based on the response.
     * @return Webpage A Webpage data object.
     * @throws InvalidUrlException When the URL fails the validity check
     * @throws InvalidUrlException  When the URL has already been scanned
     */
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

    /**
     * Helper function to check for an attribute in a \DOMNode object.
     * 
     * This is necessary as the \DOMNode object stores the attributes as an indexed array of key => value pairs.
     * @param DOMNode $node The node to scan for an existing attribute,
     * @param string $name THe name of the attribute to be scanned for.
     * @return null|string Either the value of the attribute of the \DOMNode object, or null if it is not found/set.
     */
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

    /**
     * Helper function to scan the contents of a request from Laravel's Http class and update a App\Models\Website data model instance with the results.
     * @param Webpage $webpage The Webpage object to update with the analysed results.
     * @param object $request The Http request to analyse the response of.
     * @return Webpage The updated Webpage data model object
     */
    private static function parseWebsite(Webpage $webpage, object $request): Webpage
    {

        //Prevent throwing exceptions when encountering modern HTML tags
        libxml_use_internal_errors(true);
        $startTime = microtime(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($request->body());

        $parsedUrl = parse_url($webpage->getPageUrl());

        // Attempting to do the entire analysis in one pass of the response body.
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
                        /**
                         * Intentionally no `break;` statement here as an `a` element can still contain a `#text` node
                         * which should count towards the word count of the page.
                         */
                    }
                default:
                    $children = $node->childNodes->length;
                    for ($i = 0; $i < $children; $i++) {
                        if ($node->childNodes->item($i)->nodeType === 3) {
                            $text = $node->childNodes->item($i)->textContent;
                            // Strip away common punctuation
                            $text = str_replace(array(',', '.', '!', '?', ' / '), '', $text);
                            // Compress whitespace to avoid empty "words" being counted
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
