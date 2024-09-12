<?php
namespace Skn036\Gmail\Filters;

use Illuminate\Support\Collection;

class FilterParams
{
    /**
     * includeSpamTrash on gmail filter params
     * @var bool
     */
    public $includeSpamTrash;

    /**
     * all page tokens of the list response
     * @var Collection
     */
    public $pageTokens;

    /**
     * Current page token of the list response (data fetched for the token)
     * @var string|null
     */
    public $currentPageToken;

    /**
     * Next page token of the list response (data to be fetched for the token in next request)
     * @var string|null
     */
    public $nextPageToken;

    /**
     * Per page of the list response
     * @var int
     */
    public $maxResults = 50;

    /**
     * Search string for the filter
     * @var string
     */
    public $q;

    public function __construct()
    {
        $this->pageTokens = collect([]);
    }

    /**
     * Sets filter params
     *
     * @param string $param
     * @param mixed $value
     *
     * @return static
     */
    public function setParam($param, $value)
    {
        if (property_exists($this, $param)) {
            $this->$param = $value;
        }
        return $this;
    }

    /**
     * prepares the q param on the gmail search according to the filters
     *
     * @return string|null
     */
    public function prepareQ()
    {
        if ($this->q) {
            return $this->q;
        }

        // add other filters in between

        return null;
    }

    /**
     * Returns filter params as expected by gmail list messages/threads request
     *
     * @return array
     */
    public function getParams()
    {
        $params = [];
        if ($this->includeSpamTrash) {
            $params['includeSpamTrash'] = $this->includeSpamTrash;
        }
        if ($this->currentPageToken) {
            $params['pageToken'] = $this->currentPageToken;
        }
        if ($this->maxResults) {
            $params['maxResults'] = (string) $this->maxResults;
        }

        $q = $this->prepareQ();
        if ($q) {
            $params['q'] = $q;
        }

        return $params;
    }

    /**
     * Summary of getCurrentPageIndex
     * @return int
     */
    public function getCurrentPageIndex()
    {
        $index = $this->pageTokens->search($this->currentPageToken);
        if ($index === false) {
            return -1;
        }
        return $index;
    }

    /**
     * Add currentPageToken to pageTokens
     * @return static
     */
    public function addCurrentPageTokenToPageTokens()
    {
        if (!$this->pageTokens->contains($this->currentPageToken)) {
            $this->pageTokens->push($this->currentPageToken);
        }
        return $this;
    }

    /**
     * Whether has a next page
     * @return bool
     */
    public function hasNextPage()
    {
        return !!$this->nextPageToken;
    }

    /**
     * Whether has a previous page
     * @return bool
     */
    public function hasPreviousPage()
    {
        return $this->getCurrentPageIndex() > 0;
    }

    /**
     * Get previous page token
     * @return string|null
     */
    public function getPreviousPageToken()
    {
        if (!$this->hasPreviousPage()) {
            return null;
        }
        return $this->pageTokens->get($this->getCurrentPageIndex() - 1);
    }
}
