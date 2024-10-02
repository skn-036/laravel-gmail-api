<?php
namespace Skn036\Gmail\History;

use Skn036\Gmail\Gmail;
use Skn036\Gmail\Facades\Gmail as GmailFacade;
use Skn036\Gmail\Exceptions\TokenNotValidException;

class GmailHistoryResponse
{
    /**
     * Gmail Client
     * @var Gmail|GmailFacade
     */
    protected $client;

    /**
     * Gmail service
     * @var \Google_Service_Gmail
     */
    protected $service;

    /**
     * next page token
     * @var string|null
     */
    protected $nextPageToken;

    /**
     * current page token
     * @var string|null
     */
    protected $currentPageToken;

    /**
     * max results
     * @var int
     */
    protected $maxResults = 100;

    /**
     * Label id to filter history by
     * @var string
     */
    protected $labelId = '';

    /**
     * Filter history by types
     * @var array<string> enum : 'messageAdded' | 'messageDeleted' | 'labelAdded' | 'labelRemoved'
     */
    protected $historyTypes = [];

    /**
     * Start history id
     * @var string|int
     */
    protected $startHistoryId;

    /**
     * Create a new GmailLabel instance.
     *
     * @param Gmail|GmailFacade $client
     *
     * @throws TokenNotValidException
     */
    public function __construct(Gmail|GmailFacade $client)
    {
        if (!$client->isAuthenticated()) {
            $client->throwExceptionIfNotAuthenticated();
        }

        $this->client = $client;
        $this->service = $client->initiateService();
    }

    /**
     * List histories
     *
     * @param string|null $pageToken
     *
     * @return GmailHistoriesList
     * @throws \Google\Service\Exception
     */
    public function list($pageToken = null)
    {
        return $this->getPaginatedListResponse($pageToken);
    }

    /**
     * Fetch threads from next page
     *
     * @return GmailHistoriesList
     * @throws \Google\Service\Exception
     */
    public function next()
    {
        if ($this->nextPageToken) {
            return $this->getPaginatedListResponse($this->nextPageToken);
        } else {
            return new GmailHistoriesList($this, []);
        }
    }
    /**
     * Set max results
     *
     * @param int $maxResults
     * @return static
     */
    public function maxResults(int $maxResults)
    {
        $this->maxResults = $maxResults;
        return $this;
    }

    /**
     * Set label id
     *
     * @param string $labelId
     * @return static
     */
    public function labelId(string $labelId)
    {
        $this->labelId = $labelId;
        return $this;
    }

    /**
     * Set history types
     *
     * @param string|array<string> $historyTypes enum : 'messageAdded' | 'messageDeleted' | 'labelAdded' | 'labelRemoved'
     * @return static
     */
    public function historyTypes(string|array $historyTypes)
    {
        if (!is_array($historyTypes)) {
            $historyTypes = [$historyTypes];
        }
        $this->historyTypes = $historyTypes;
        return $this;
    }

    /**
     * Set start history id
     *
     * @param string $startHistoryId
     * @return static
     */
    public function startHistoryId(string $startHistoryId)
    {
        $this->startHistoryId = $startHistoryId;
        return $this;
    }

    /**
     * Get next page token
     *
     * @return string|null
     */
    public function getNextPageToken()
    {
        return $this->nextPageToken;
    }

    /**
     * Get current page token
     *
     * @return string|null
     */
    public function getCurrentPageToken()
    {
        return $this->currentPageToken;
    }

    protected function getHistoryListResponse(array $optParams = [])
    {
        return $this->service->users_history->listUsersHistory('me', $optParams);
    }

    /**
     * Prepare filter params
     *
     * @return array
     */
    protected function prepareFilterParams()
    {
        $params = [];
        if ($this->currentPageToken) {
            $params['pageToken'] = $this->currentPageToken;
        }
        if ($this->maxResults) {
            $params['maxResults'] = $this->maxResults;
        }
        if ($this->labelId) {
            $params['labelId'] = $this->labelId;
        }
        if ($this->historyTypes) {
            $params['historyTypes'] = $this->historyTypes;
        }
        if ($this->startHistoryId) {
            $params['startHistoryId'] = $this->startHistoryId;
        }

        return $params;
    }

    /**
     * Get paginated list response
     *
     * @param string|null $currentPageToken
     * @return GmailHistoriesList
     */
    protected function getPaginatedListResponse($currentPageToken = null)
    {
        if (!$this->startHistoryId) {
            throw new \Exception('startHistoryId is required');
        }
        $this->currentPageToken = $currentPageToken;

        $optParams = $this->prepareFilterParams();
        $response = $this->getHistoryListResponse($optParams);

        if ($nextPageToken = $response->getNextPageToken()) {
            $this->nextPageToken = $nextPageToken;
        } else {
            $this->nextPageToken = null;
        }

        $histories = $response->getHistory();

        if (!$histories || !is_array($histories) || !count($histories)) {
            return new GmailHistoriesList($this, []);
        }
        $processedHistories = array_map(
            fn($history) => new GmailHistory($history),
            array_values($histories)
        );

        return new GmailHistoriesList($this, $processedHistories);
    }
}
