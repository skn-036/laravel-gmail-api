<?php
namespace Skn036\Gmail\Message;

use Illuminate\Support\Collection;
use Skn036\Gmail\Message\FetchMessage;

class GmailMessageCollection extends Collection
{
    /**
     * Summary of fetchMessage
     * @var FetchMessage
     */
    private $fetchMessage;

    /**
     * Summary of total
     * @var int|null
     */
    public $total;

    /**
     * Current page number
     * @var int|null
     */
    public $page;

    /**
     * Start of current page
     * @var int|null
     */
    public $from;

    /**
     * End of current page
     * @var int|null
     */
    public $to;

    /**
     * Per page
     * @var int
     */
    public $perPage;

    /**
     * GmailMessageCollection constructor.
     *
     * @param FetchMessage $fetchMessage
     * @param array $items
     * @param int|null $estimatedMessagesCount
     */
    public function __construct($fetchMessage, $items = [], $estimatedMessagesCount = null)
    {
        $this->fetchMessage = $fetchMessage;
        $this->total = $estimatedMessagesCount;

        $this->setPaginationParams();

        parent::__construct($items);
    }

    private function setPaginationParams()
    {
        $currentPageIndex = $this->fetchMessage->filters->getCurrentPageIndex();

        $this->page = $currentPageIndex >= 0 ? $currentPageIndex + 1 : null;
        $this->perPage = $this->fetchMessage->filters->maxResults;

        $from = null;
        $to = null;
        if (null !== $this->page) {
            $from = ($this->page - 1) * $this->perPage + 1;
            $to = $this->page * $this->perPage;
            $to = $to > $this->total ? $this->total : $to;
        }
        $this->from = $from;
        $this->to = $to;

        return $this;
    }

    /**
     * Whether next page exists
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return $this->fetchMessage->filters->hasNextPage();
    }

    /**
     * Whether previous page exists
     *
     * @return bool
     */
    public function hasPreviousPage()
    {
        return $this->fetchMessage->filters->hasPreviousPage();
    }

    /**
     * Get the token of next page
     * @return string|null
     */
    public function getNextPageToken()
    {
        return $this->fetchMessage->filters->nextPageToken;
    }

    /**
     * Get the token of previous page
     * @return string|null
     */
    public function getPreviousPageToken()
    {
        return $this->fetchMessage->filters->getPreviousPageToken();
    }

    /**
     * Fetch date of next page
     * @return static
     */
    public function next()
    {
        return $this->fetchMessage->next();
    }

    /**
     * Fetch date of previous page
     * @return static
     */
    public function previous()
    {
        return $this->fetchMessage->previous();
    }
}
