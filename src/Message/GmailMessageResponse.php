<?php
namespace Skn036\Gmail\Message;

use Skn036\Gmail\Gmail;
use Skn036\Gmail\Filters\GmailFilter;
use Skn036\Gmail\Message\Sendable\Email;
use Skn036\Gmail\Facades\Gmail as GmailFacade;
use Skn036\Gmail\Exceptions\TokenNotValidException;

class GmailMessageResponse extends GmailFilter
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
     * Create a new GmailMessage instance.
     *
     * @param Gmail $client
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
     * List messages
     *
     * @param string|null $pageToken
     * @return GmailMessagesList
     */
    public function list(string|null $pageToken = null)
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
    public function get(string $id)
    {
        $message = $this->getGmailMessageResponse($id);
        return new GmailMessage($message, $this->client);
    }

    /**
     * Creates a new sendable email instance
     *
     * @return Email
     */
    public function create()
    {
        return new Email($this->client);
    }

    /**
     * Creates a replyable instance of the message setting proper headers, subject and thread id.
     * This will not set the "to", "cc", "body" of the message.
     * This is because, most of the time replied message will be edited on the user interface before sending.
     * So it should be more appropriate to set these values by the public api provided on \Skn036\Gmail\Message\Sendable\Email.
     *
     * @param GmailMessage|string $message
     * @return Email
     */
    public function createReply(GmailMessage|string $message)
    {
        if (!($message instanceof GmailMessage)) {
            $message = $this->get($message);
        }
        return $message->createReply();
    }

    /**
     * Creates a forwarding instance of the message setting proper headers, subject and thread id.
     * This will not set the "attachments", "to", "cc", "body" of the message.
     * This is because, most of the time forwarded message will be edited on the user interface before sending.
     * So it should be more appropriate to set these values by the public api provided on \Skn036\Gmail\Message\Sendable\Email.
     *
     * @param GmailMessage|string $message
     * @return Email
     */
    public function createForward(GmailMessage|string $message)
    {
        if (!($message instanceof GmailMessage)) {
            $message = $this->get($message);
        }
        return $message->createForward();
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
            fn($message) => new GmailMessage($message, $this->client),
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
            // @phpstan-ignore-next-line
            $batch->add($this->getGmailMessageResponse($message->getId()));
        }

        return $batch->execute();
    }
}
