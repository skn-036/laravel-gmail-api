<?php
namespace Skn036\Gmail\Message;

use Skn036\Gmail\Gmail;
use Illuminate\Support\Collection;
use Skn036\Gmail\Filters\FilterParams;

class FetchMessage
{
    /**
     * Gmail Client
     * @var Gmail
     */
    protected $client;

    /**
     * Gmail service
     * @var \Google_Service_Gmail
     */
    protected $service;

    /**
     * Optional parameter for getting multiple emails
     *
     * @var FilterParams
     */
    public $filters;

    /**
     * Previous page token
     *
     * @var Collection<int, string|null>
     */
    public $pageTokens = [];

    /**
     * Create a new GmailMessage instance.
     *
     * @param Gmail $client
     *
     * @return void
     */
    public function __construct($client)
    {
        $this->client = $client;
        $this->service = $client->initiateService();
        $this->filters = new FilterParams();
    }

    /**
     * List messages
     *
     * @param int|null $perPage
     * @return GmailMessageCollection
     */
    public function list($perPage = null)
    {
        $this->filters->pageTokens = collect([null]);
        $this->maxResults($perPage);
        return $this->getPaginatedListResponse(null);
    }

    /**
     * Fetch messages from next page
     *
     * @return GmailMessageCollection
     */
    public function next()
    {
        if ($this->filters->hasNextPage()) {
            return $this->getPaginatedListResponse($this->filters->nextPageToken);
        } else {
            return new GmailMessageCollection($this, []);
        }
    }

    /**
     * Fetch messages from previous page
     *
     * @return GmailMessageCollection
     */
    public function previous()
    {
        if (!$this->filters->hasPreviousPage()) {
            return new GmailMessageCollection($this, []);
        }
        $previousPageToken = $this->filters->getPreviousPageToken();

        // clear all the page tokens including the previousPageToken
        // previousPageToken will be added to pageTokens in getPaginatedListResponse
        $previousTokenIndex = $this->pageTokens->search($previousPageToken) ?: 0;
        $this->pageTokens = $this->pageTokens->take($previousTokenIndex);

        return $this->getPaginatedListResponse($previousPageToken);
    }

    /**
     * Get message by id
     *
     * @param string $id
     * @return GmailMessage
     */
    public function get($id)
    {
        $message = $this->getGmailMessageResponse($id);
        return new GmailMessage($message);
    }

    /**
     * sets param to fetch spam and trash messages on list response
     *
     * @return static
     */
    public function includeSpamTrash()
    {
        $this->filters->setParam('includeSpamTrash', true);
        return $this;
    }

    /**
     * sets param to fetch no of messages with the given query
     *
     * @param string|int|null $perPage
     * @return static
     */
    public function maxResults($perPage)
    {
        if ($perPage && (int) $perPage > 0) {
            $this->filters->setParam('maxResults', $perPage);
        }
        return $this;
    }

    /**
     * List messages request to gmail
     *
     * @param array $params
     * @return \Google_Service_Gmail_ListMessagesResponse
     */
    protected function getGmailMessageListResponse($params = [])
    {
        return $this->service->users_messages->listUsersMessages('me', $params);
    }

    /**
     * Get message request to gmail
     *
     * @param string $id
     * @return \Google_Service_Gmail_Message
     */
    protected function getGmailMessageResponse($id)
    {
        return $this->service->users_messages->get('me', $id);
    }

    /**
     * Get paginated list response
     *
     * @param string|null $currentPageToken
     * @return GmailMessageCollection
     */
    protected function getPaginatedListResponse($currentPageToken = null)
    {
        $this->filters->setParam('currentPageToken', $currentPageToken);
        $this->filters->addCurrentPageTokenToPageTokens();

        $params = $this->filters->getParams();
        $response = $this->getGmailMessageListResponse($params);

        if ($nextPageToken = $response->getNextPageToken()) {
            $this->filters->setParam('nextPageToken', $nextPageToken);
        } else {
            $this->filters->setParam('nextPageToken', null);
        }

        $estimatedMessagesCount = $response->getResultSizeEstimate();
        $messages = $response->getMessages();

        if (!$messages || !is_array($messages) || !count($messages)) {
            return new GmailMessageCollection($this, [], $estimatedMessagesCount);
        }
        $processedMessages = array_map(
            fn($message) => new GmailMessage($message),
            array_values($this->getMessageDetailsOnBatch($messages))
        );

        return new GmailMessageCollection($this, $processedMessages, $estimatedMessagesCount);
    }

    /**
     * From list messages response, get detailed response of every messages
     *
     * @param array<\Google_Service_Gmail_Message> $messages
     * @return array<\Google_Service_Gmail_Message>
     */
    protected function getMessageDetailsOnBatch($messages)
    {
        $this->client->setUseBatch(true);
        $batch = $this->service->createBatch();
        foreach ($messages as $message) {
            $batch->add($this->getGmailMessageResponse($message->getId()));
        }

        return $batch->execute();
    }
}
