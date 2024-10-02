<?php
namespace Skn036\Gmail\Message;

use Skn036\Gmail\Gmail;
use Illuminate\Support\Collection;
use Skn036\Gmail\Filters\GmailFilter;
use Skn036\Gmail\Draft\Sendable\Draft;
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
     * List messages
     *
     * @param string|null $pageToken
     *
     * @return GmailMessagesList
     * @throws \Google\Service\Exception
     */
    public function list(string|null $pageToken = null)
    {
        return $this->getPaginatedListResponse($pageToken);
    }

    /**
     * Fetch messages from next page
     *
     * @return GmailMessagesList
     * @throws \Google\Service\Exception
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
     *
     * @return GmailMessage
     * @throws \Google\Service\Exception
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
     * @param GmailMessage|string $messageOrMessageId
     *
     * @return Email
     * @throws \Google\Service\Exception
     */
    public function createReply(GmailMessage|string $messageOrMessageId)
    {
        $message = $this->resolveMessageFromInstanceOrId($messageOrMessageId);
        return $message->createReply();
    }

    /**
     * Creates a forwarding instance of the message setting proper headers, subject and thread id.
     * This will not set the "attachments", "to", "cc", "body" of the message.
     * This is because, most of the time forwarded message will be edited on the user interface before sending.
     * So it should be more appropriate to set these values by the public api provided on \Skn036\Gmail\Message\Sendable\Email.
     *
     * @param GmailMessage|string $messageOrMessageId
     *
     * @return Email
     * @throws \Google\Service\Exception
     */
    public function createForward(GmailMessage|string $messageOrMessageId)
    {
        $message = $this->resolveMessageFromInstanceOrId($messageOrMessageId);
        return $message->createForward();
    }

    /**
     * Creates a replying/forwarding draft instance of the message setting proper headers, subject and thread id.
     * This will not set the "attachments", "to", "cc", "body" of the message.
     * This is because, most of the time forwarded message will be edited on the user interface before sending.
     * So it should be more appropriate to set these values by the public api provided on \Skn036\Gmail\Draft\Sendable\Draft.
     *
     * @param GmailMessage|string $messageOrMessageId
     *
     * @return Draft
     * @throws \Google\Service\Exception
     */
    public function createDraft(GmailMessage|string $messageOrMessageId)
    {
        $message = $this->resolveMessageFromInstanceOrId($messageOrMessageId);
        return $message->createDraft();
    }

    /**
     * Modify labels of a message
     *
     * @param GmailMessage|string $messageOrMessageId
     * @param string|array $addLabelIds
     * @param string|array $removeLabelIds
     * @param array $optParams
     *
     * @return GmailMessage
     * @throws \Google\Service\Exception
     */
    public function modifyLabels(
        GmailMessage|string $messageOrMessageId,
        string|array $addLabelIds = [],
        string|array $removeLabelIds = [],
        array $optParams = []
    ) {
        $message = $this->resolveMessageFromInstanceOrId($messageOrMessageId);
        return $message->modifyLabels($addLabelIds, $removeLabelIds, $optParams);
    }

    /**
     * Add labels to a message
     *
     * @param GmailMessage|string $messageOrMessageId
     * @param string|array $labelIds
     * @param array $optParams
     *
     * @return GmailMessage
     * @throws \Google\Service\Exception
     */
    public function addLabels(
        GmailMessage|string $messageOrMessageId,
        string|array $labelIds = [],
        array $optParams = []
    ) {
        return $this->modifyLabels($messageOrMessageId, $labelIds, [], $optParams);
    }

    /**
     * Add labels to a message
     *
     * @param GmailMessage|string $messageOrMessageId
     * @param string|array $labelIds
     * @param array $optParams
     *
     * @return GmailMessage
     * @throws \Google\Service\Exception
     */
    public function removeLabels(
        GmailMessage|string $messageOrMessageId,
        string|array $labelIds = [],
        array $optParams = []
    ) {
        return $this->modifyLabels($messageOrMessageId, [], $labelIds, $optParams);
    }

    /**
     * Send a message to trash
     *
     * @param GmailMessage|string $messageOrMessageId
     * @param array $optParams
     *
     * @return GmailMessage
     * @throws \Google\Service\Exception
     */
    public function trash(GmailMessage|string $messageOrMessageId, array $optParams = [])
    {
        $message = $this->resolveMessageFromInstanceOrId($messageOrMessageId);
        return $message->trash($optParams);
    }

    /**
     * Remove a message from trash
     *
     * @param GmailMessage|string $messageOrMessageId
     * @param array $optParams
     *
     * @return GmailMessage
     * @throws \Google\Service\Exception
     */
    public function untrash(GmailMessage|string $messageOrMessageId, array $optParams = [])
    {
        $message = $this->resolveMessageFromInstanceOrId($messageOrMessageId);
        return $message->untrash($optParams);
    }

    /**
     * Permanently delete a message
     * Full mailbox permission scopes needed to execute this action.
     * For more info: https://developers.google.com/gmail/api/auth/scopes
     *
     * @param GmailMessage|string $messageOrMessageId
     * @param array $optParams
     *
     * @return void
     * @throws \Google\Service\Exception
     */
    public function delete(GmailMessage|string $messageOrMessageId, array $optParams = [])
    {
        $message = $this->resolveMessageFromInstanceOrId($messageOrMessageId);
        $message->delete($optParams);
    }

    /**
     * Batch modify messages
     *
     * @param Collection<GmailMessage|string>|array<GmailMessage|string> $messagesOrMessageIds
     * @param string|array $addLabelIds
     * @param string|array $removeLabelIds
     * @param array $optParams
     *
     * @return mixed
     */
    public function batchModifyLabels(
        Collection|array $messagesOrMessageIds,
        string|array $addLabelIds = [],
        string|array $removeLabelIds = [],
        array $optParams = []
    ) {
        $messageIds = $this->resolveMessageIdsFromCollectionOrArray($messagesOrMessageIds);
        if (!is_array($addLabelIds)) {
            $addLabelIds = [$addLabelIds];
        }
        if (!is_array($removeLabelIds)) {
            $removeLabelIds = [$removeLabelIds];
        }

        $batchModifyRequest = new \Google_Service_Gmail_BatchModifyMessagesRequest();

        $batchModifyRequest->setIds($messageIds);
        $batchModifyRequest->setAddLabelIds($addLabelIds);
        $batchModifyRequest->setRemoveLabelIds($removeLabelIds);

        return $this->service->users_messages->batchModify('me', $batchModifyRequest, $optParams);
    }

    /**
     * Batch add labels to messages
     *
     * @param Collection<GmailMessage|string>|array<GmailMessage|string> $messagesOrMessageIds
     * @param string|array $labelIds
     * @param array $optParams
     *
     * @return mixed
     */
    public function batchAddLabels(
        Collection|array $messagesOrMessageIds,
        string|array $labelIds = [],
        array $optParams = []
    ) {
        return $this->batchModifyLabels($messagesOrMessageIds, $labelIds, [], $optParams);
    }

    /**
     * Batch remove labels from messages
     *
     * @param Collection<GmailMessage|string>|array<GmailMessage|string> $messagesOrMessageIds
     * @param string|array $labelIds
     * @param array $optParams
     *
     * @return mixed
     */
    public function batchRemoveLabels(
        Collection|array $messagesOrMessageIds,
        string|array $labelIds = [],
        array $optParams = []
    ) {
        return $this->batchModifyLabels($messagesOrMessageIds, [], $labelIds, $optParams);
    }

    /**
     * Batch delete messages
     *
     * @param Collection<GmailMessage|string>|array<GmailMessage|string> $messagesOrMessageIds
     * @param array $optParams
     *
     * @return mixed
     */
    public function batchDelete(Collection|array $messagesOrMessageIds, array $optParams = [])
    {
        $messageIds = $this->resolveMessageIdsFromCollectionOrArray($messagesOrMessageIds);

        $batchDeleteRequest = new \Google_Service_Gmail_BatchDeleteMessagesRequest();
        $batchDeleteRequest->setIds($messageIds);

        return $this->service->users_messages->batchDelete('me', $batchDeleteRequest, $optParams);
    }

    /**
     * List messages request to gmail
     *
     * @param array $optParams
     *
     * @return \Google_Service_Gmail_ListMessagesResponse
     * @throws \Google\Service\Exception
     */
    protected function getGmailMessageListResponse($optParams = [])
    {
        return $this->service->users_messages->listUsersMessages('me', $optParams);
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

        $optParams = $this->prepareFilterParams();
        $response = $this->getGmailMessageListResponse($optParams);

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

    /**
     * Resolve message from instance or id
     *
     * @param GmailMessage|string $messageOrMessageId
     *
     * @return GmailMessage
     * @throws \Google\Service\Exception
     */
    private function resolveMessageFromInstanceOrId($messageOrMessageId)
    {
        if ($messageOrMessageId instanceof GmailMessage) {
            return $messageOrMessageId;
        }
        return $this->get($messageOrMessageId);
    }

    /**
     * Resolve message ids from collection or array
     *
     * @param Collection<GmailMessage|string>|array<GmailMessage|string> $messagesOrMessageIds
     *
     * @return array<string>
     * @throws \Exception
     */
    private function resolveMessageIdsFromCollectionOrArray($messagesOrMessageIds)
    {
        if (!$messagesOrMessageIds instanceof Collection && !is_array($messagesOrMessageIds)) {
            throw new \Exception('Messages or MessageIds Collection or array is required');
        }

        if (!$messagesOrMessageIds instanceof Collection) {
            $messagesOrMessageIds = collect($messagesOrMessageIds);
        }

        return $messagesOrMessageIds
            ->map(
                fn($messageOrMessageId) => $messageOrMessageId instanceof GmailMessage
                    ? $messageOrMessageId->id
                    : $messageOrMessageId
            )
            ->values()
            ->all();
    }
}
