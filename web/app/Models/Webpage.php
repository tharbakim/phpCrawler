<?php

namespace App\Models;

class Webpage
{
    /**
     * Array of URLs representing images that are present on a page.
     * @var array
     */
    private array $images = array();

    /**
     * Array of URLs representing hyperlinks that do not point to a different host/domain.
     * @var array
     */
    private array $internalUrls = array();

    /**
     * Array of URLs representing hyperlinks that do point to a different host/domain.
     * @var array
     */
    private array $externalUrls = array();

    /**
     * The elapsed time it took to load the webpage.
     * @var string
     */
    private string $loadTime;

    /**
     * The elapsed time it took to scan and analyse the webpage.
     * @var string
     */
    private string $parseTime;

    /**
     * The number of nodes scanned when analysing the webpage.
     * @var int
     */
    private int $nodesScanned = 0;

    /**
     * The number of words encountered in the webpage.
     * @var int
     */
    private int $wordCount = 0;

    /**
     * The title of the webpage once loaded.
     * @var string
     */
    private string $pageTitle = '';

    /**
     * The HTTP respone code for the loaded URL.
     * @var int
     */
    private int $httpResponse;

    /**
     * The URL loaded for, and represented by this data model.
     * @var string
     */
    private string $pageUrl;

    public function __construct($url)
    {
        $this->pageUrl = $url;
    }

    /**
     * Returns JSON representation of the data model.
     * @return string 
     */
    public function __toString(): string
    {
        return json_encode($this->getResult());
    }

    /**
     * Provides access to an array of properties 
     * @return array 
     */
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

    /**
     * Appends a string to the list of image URLs encountered.
     * @param string $url 
     * @return void 
     */
    public function addImage(string $url): void
    {
        array_push($this->images, $url);
    }

    /**
     * Getter for the list of image URLs encountered.
     * @return array 
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Appends a string to the list of internal URLs encountered.
     * @param string $url 
     * @return void 
     */
    public function addInternalUrl(string $url): void
    {
        array_push($this->internalUrls, $url);
    }

    /**
     * Getter for the list of internal URLs encountered.
     * @return array 
     */
    public function getInternalUrls(): array
    {
        return $this->internalUrls;
    }

    /**
     * Append a string to the list of external URLs encountered.
     * @param string $url 
     * @return void 
     */
    public function addExternalUrl(string $url): void
    {
        array_push($this->externalUrls, $url);
    }

    /**
     * Getter for the list of external URLs encountered.
     * @return array 
     */
    public function getExternalUrls(): array
    {
        return $this->externalUrls;
    }

    /**
     * Getter for the page load time.
     * @return string 
     */
    public function getLoadTime(): string
    {
        return $this->loadTime;
    }

    /**
     * Setter for the page load time.
     * @param string $time 
     * @return void 
     */
    public function setLoadTime(string $time): void
    {
        $this->loadTime = $time;
    }

    /**
     * Getter for the page parse time.
     * @return string 
     */
    public function getParseTime(): string
    {
        return $this->parseTime;
    }

    /**
     * Setter for the page parse time.
     * @param string $time 
     * @return void 
     */
    public function setParseTime(string $time): void
    {
        $this->parseTime = $time;
    }

    /**
     * Increment the count of nodes scanned while analysing the website represented by this data model.
     */
    public function addNodeScanned(): void
    {
        $this->nodesScanned++;
    }

    /**
     * Getter for the count of nodes scanned.
     * @return int 
     */
    public function getNodesScanned(): int
    {
        return $this->nodesScanned;
    }

    /**
     * Add to the ongoing word count for the website represented by this data model.
     * @param int $words The number of words to add to the count. Defaults to 1.
     * @return void 
     */
    public function addWordCount(int $words = 1): void
    {
        $this->wordCount += $words;
    }

    /**
     * Getter for the page word count.
     * @return int 
     */
    public function getWordCount(): int
    {
        return $this->wordCount;
    }

    /**
     * Getter for the page title.
     * @return string 
     */
    public function getPageTitle(): string
    {
        return $this->pageTitle;
    }

    /**
     * Setter for the page title.
     * @param string $title 
     * @return void 
     */
    public function setPageTitle(string $title): void
    {
        $this->pageTitle = $title;
    }

    /**
     * Getter for the HTTP response code.
     * @return int 
     */
    public function getHttpResponse(): int
    {
        return $this->httpResponse;
    }

    /**
     * Setter for the HTTP response code.
     * @param int $code 
     * @return void 
     */
    public function setHttpResponse(int $code): void
    {
        $this->httpResponse = $code;
    }

    /**
     * Getter for the page URL
     * @return string 
     */
    public function getPageUrl(): string
    {
        return $this->pageUrl;
    }
}
