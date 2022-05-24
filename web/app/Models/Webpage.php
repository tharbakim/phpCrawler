<?php

namespace App\Models;

class Webpage
{
    private array $images = array();
    private array $internalUrls = array();
    private array $externalUrls = array();
    private string $loadTime;
    private string $parseTime;
    private int $nodesScanned = 0;
    private int $wordCount = 0;
    private string $pageTitle = '';
    private int $httpResponse;
    private string $pageUrl;

    public function __construct($url)
    {
        $this->pageUrl = $url;
    }

    public function __toString(): string
    {
        return json_encode($this->getResult());
    }

    public function getResult(): array
    {
        return array(
            "images" => $this->images,
            "internalUrls" => $this->internalUrls,
            "externalUrls" => $this->externalUrls,
            "loadTime" => $this->loadTime,
            "parseTime" => $this->parseTime,
            "nodesScanned" => $this->nodesScanned,
            "wordCount" => $this->wordCount,
            "pageTitle" => $this->pageTitle,
            "httpResponse" => $this->httpResponse,
            "pageUrl" => $this->pageUrl
        );
    }

    public function addImage(string $url): void
    {
        array_push($this->images, $url);
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function addInternalUrl(string $url): void
    {
        array_push($this->internalUrls, $url);
    }

    public function getInternalUrls(): array
    {
        return $this->internalUrls;
    }

    public function addExternalUrl(string $url): void
    {
        array_push($this->externalUrls, $url);
    }

    public function getExternalUrls(): array
    {
        return $this->externalUrls;
    }

    public function getLoadTime(): string
    {
        return $this->loadTime;
    }

    public function setLoadTime(string $time): void
    {
        $this->loadTime = $time;
    }

    public function getParseTime(): string
    {
        return $this->parseTime;
    }

    public function setParseTime(string $time): void
    {
        $this->parseTime = $time;
    }

    public function addNodeScanned(): void
    {
        $this->nodesScanned++;
    }

    public function getNodesScanned(): int
    {
        return $this->nodesScanned;
    }

    public function addWordCount(int $words = 1): void
    {
        $this->wordCount += $words;
    }

    public function getWordCount(): int
    {
        return $this->wordCount;
    }

    public function getPageTitle(): string
    {
        return $this->pageTitle;
    }

    public function setPageTitle(string $title): void
    {
        $this->pageTitle = $title;
    }

    public function getHttpResponse(): int
    {
        return $this->httpResponse;
    }

    public function setHttpResponse(int $code): void
    {
        $this->httpResponse = $code;
    }

    public function getPageUrl(): string
    {
        return $this->pageUrl;
    }
}
