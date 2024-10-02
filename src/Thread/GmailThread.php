<?php
namespace Skn036\Gmail\Thread;

use Skn036\Gmail\Gmail;
use Illuminate\Support\Collection;
use Skn036\Gmail\Message\GmailMessage;
use Skn036\Gmail\Facades\Gmail as GmailFacade;

class GmailThread
{
    /**
     * Thread from gmail
     * @var \Google_Service_Gmail_Thread|\Google\Service\Gmail\Thread
     */
    protected $thread;

    /**
     * Gmail Client
     * @var Gmail|GmailFacade
     */
    private $client;

    /**
     * Message Id
     * @var string
     */
    public $id;

    /**
     * Messages on the thread
     * @var Collection<GmailMessage>
     */
    public $messages;

    /**
     * Thread history id
     * @var string
     */
    public $historyId;

    /**
     * Thread snippet
     * @var string
     */
    public $snippet;

    /**
     * Summary of __construct
     * @param \Google_Service_Gmail_Thread|\Google\Service\Gmail\Thread $thread
     * @param Gmail|GmailFacade $client
     *
     * @throws \Exception
     */
    public function __construct($thread, Gmail|GmailFacade $client)
    {
        if ($thread instanceof \Exception) {
            throw $thread;
        }
        if (!($thread instanceof \Google_Service_Gmail_Thread)) {
            throw new \Exception('thread is not instance of \Google_Service_Gmail_Thread');
        }

        $this->client = $client;
        $this->thread = $thread;

        $this->prepareThread();
    }

    /**
     * Modify the labels of the message
     *
     * @param array|string $addedLabelIds
     * @param array|string $removedLabelIds
     * @param array $optParams
     *
     * @return static
     */
    public function modifyLabels(
        array|string $addedLabelIds = [],
        array|string $removedLabelIds = [],
        array $optParams = []
    ) {
        if (is_string($addedLabelIds)) {
            $addedLabelIds = [$addedLabelIds];
        }
        if (is_string($removedLabelIds)) {
            $removedLabelIds = [$removedLabelIds];
        }
        if (!count($addedLabelIds) && !count($removedLabelIds)) {
            return $this;
        }

        $modify = new \Google_Service_Gmail_ModifyThreadRequest();
        if (count($addedLabelIds) > 0) {
            $modify->setAddLabelIds($addedLabelIds);
        }
        if (count($removedLabelIds) > 0) {
            $modify->setRemoveLabelIds($removedLabelIds);
        }

        $service = $this->client->initiateService();
        $service->users_threads->modify('me', $this->id, $modify, $optParams);

        return $this->updateInstance();
    }

    /**
     * Add labels of the message
     *
     * @param array|string $labelIds
     * @param array $optParams
     *
     * @return static
     */
    public function addLabels(array|string $labelIds = [], array $optParams = [])
    {
        return $this->modifyLabels($labelIds, [], $optParams);
    }

    /**
     * Remove labels of the message
     *
     * @param array|string $labelIds
     * @param array $optParams
     *
     * @return static
     */
    public function removeLabels(array|string $labelIds = [], array $optParams = [])
    {
        return $this->modifyLabels([], $labelIds, $optParams);
    }

    /**
     * Move the message to trash
     *
     * @param array $optParams
     *
     * @return static
     */
    public function trash($optParams = [])
    {
        $service = $this->client->initiateService();
        $service->users_threads->trash('me', $this->id, $optParams);
        return $this->updateInstance();
    }

    /**
     * Move the message from trash
     *
     * @param array $optParams
     *
     * @return static
     */
    public function untrash($optParams = [])
    {
        $service = $this->client->initiateService();
        $service->users_threads->untrash('me', $this->id, $optParams);
        return $this->updateInstance();
    }

    /**
     * Permanently deletes the thread
     * Full mailbox permission scopes needed to execute this action.
     * For more info: https://developers.google.com/gmail/api/auth/scopes
     *
     * @param array $optParams
     *
     * @return void
     */
    public function delete($optParams = [])
    {
        $service = $this->client->initiateService();
        $service->users_threads->delete('me', $this->id, $optParams);
    }

    /**
     * Get the raw message
     * @return \Google_Service_Gmail_Thread|\Google\Service\Gmail\Thread
     */
    public function getRawThread()
    {
        return $this->thread;
    }

    protected function setId()
    {
        $this->id = $this->thread->getId();
        return $this;
    }

    /**
     * Set the messages of the thread
     * @return static
     */
    protected function setMessages()
    {
        $this->messages = collect($this->thread->getMessages())->map(
            fn($message) => new GmailMessage($message, $this->client)
        );
        return $this;
    }

    /**
     * Set the history id of the thread
     * @return static
     */
    protected function setHistoryId()
    {
        $this->historyId = $this->thread->getHistoryId();
        return $this;
    }

    /**
     * Set the snippet of the thread
     * @return static
     */
    protected function setSnippet()
    {
        $this->snippet = $this->thread->getSnippet();
        return $this;
    }

    /**
     * Prepare the thread from \Google_Service_Gmail_Thread instance
     * @return static
     */
    protected function prepareThread()
    {
        return $this->setId()->setMessages()->setHistoryId()->setSnippet();
    }

    /**
     * Update the instance from fetching thread again
     * @return static
     */
    protected function updateInstance()
    {
        $service = $this->client->initiateService();
        $this->thread = $service->users_threads->get('me', $this->id);
        return $this->prepareThread();
    }
}
