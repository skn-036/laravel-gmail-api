<?php
namespace Skn036\Gmail\Message;

use Skn036\Gmail\Filters\GmailFilter;
use Skn036\Gmail\Gmail;

class MessageResponse extends GmailFilter
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
    }

    /**
     * List messages
     *
     * @param string|null $pageToken
     * @return GmailMessagesList
     */
    public function list($pageToken = null)
    {
        return $this->getPaginatedListResponse($pageToken);
    }

    /**
     * Fetch messages from next page
     * @return GmailMessagesList
     */
    public function next()
    {
        if ($this->hasNextPage()) {
            return $this->getPaginatedListResponse($this->nextPageToken);
        } else {
            return new GmailMessagesList($this, []);
        }
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
     * @return GmailMessagesList
     */
    protected function getPaginatedListResponse($currentPageToken = null)
    {
        $this->setFilterParam('currentPageToken', $currentPageToken);

        $params = $this->prepareFilterParams();
        $response = $this->getGmailMessageListResponse($params);

        if ($nextPageToken = $response->getNextPageToken()) {
            $this->setFilterParam('nextPageToken', $nextPageToken);
        } else {
            $this->setFilterParam('nextPageToken', null);
        }

        $estimatedMessagesCount = $response->getResultSizeEstimate();
        $messages = $response->getMessages();

        if (!$messages || !is_array($messages) || !count($messages)) {
            return new GmailMessagesList($this, [], $estimatedMessagesCount);
        }
        $processedMessages = array_map(
            fn($message) => new GmailMessage($message),
            array_values($this->getMessageDetailsOnBatch($messages))
        );

        return new GmailMessagesList($this, $processedMessages, $estimatedMessagesCount);
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
