<?php
namespace Skn036\Gmail\History;

use Illuminate\Support\Collection;
use Skn036\Gmail\History\GmailHistoryResponse;
use Skn036\Gmail\History\GmailHistory;

class GmailHistoriesList
{
    /**
     * Histories from gmail
     * @var Collection<GmailHistory>|array<GmailHistory>
     */
    public $histories;

    /**
     * Next page token if exists
     * @var string|null
     */
    public $currentPageToken;

    /**
     * Next page token if exists
     * @var string|null
     */
    public $nextPageToken;

    /**
     * Has next page
     * @var bool
     */
    public $hasNextPage;

    /**
     * Gmail fetch service
     * @var GmailHistoryResponse
     */
    private $response;

    /**
     * Summary of __construct
     *
     * @param Collection<GmailHistory>|array<GmailHistory> $histories
     * @param GmailHistoryResponse $response
     */
    public function __construct(GmailHistoryResponse $response, Collection|array $histories = [])
    {
        $this->histories = collect($histories);
        $this->response = $response;

        $this->hasNextPage = !!$this->response->getNextPageToken();
        $this->currentPageToken = $this->response->getCurrentPageToken();
        $this->nextPageToken = $this->response->getNextPageToken();
    }

    /**
     * Fetch the histories from next page
     * @return \Skn036\Gmail\History\GmailHistoriesList
     */
    public function next()
    {
        return $this->response->next();
    }
}
