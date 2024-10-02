<?php

namespace Skn036\Gmail\Thread;

use Skn036\Gmail\Gmail;
use Skn036\Gmail\Filters\GmailFilter;
use Skn036\Gmail\Facades\Gmail as GmailFacade;
use Skn036\Gmail\Exceptions\TokenNotValidException;

class GmailThreadResponse extends GmailFilter
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
     * Create a new GmailThread instance.
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
     * List threads
     *
     * @param string|null $pageToken
     *
     * @return GmailThreadsList
     * @throws \Google\Service\Exception
     */
    public function list(string|null $pageToken = null)
    {
        return $this->getPaginatedListResponse($pageToken);
    }

    /**
     * Fetch threads from next page
     *
     * @return GmailThreadsList
     * @throws \Google\Service\Exception
     */
    public function next()
    {
        if ($this->hasNextPage()) {
            return $this->getPaginatedListResponse($this->nextPageToken);
        } else {
            return new GmailThreadsList($this, []);
        }
    }

    /**
     * Get thread by id
     *
     * @param string $id
     *
     * @return GmailThread
     * @throws \Google\Service\Exception
     */
    public function get(string $id)
    {
        $thread = $this->getGmailThreadResponse($id);
        return new GmailThread($thread, $this->client);
    }

    /**
     * Modify labels of a thread
     *
     * @param GmailThread|string $threadOrThreadId
     * @param string|array $addLabelIds
     * @param string|array $removeLabelIds
     * @param array $optParams
     *
     * @return GmailThread
     * @throws \Google\Service\Exception
     */
    public function modifyLabels(
        GmailThread|string $threadOrThreadId,
        string|array $addLabelIds = [],
        string|array $removeLabelIds = [],
        array $optParams = []
    ) {
        $thread = $this->resolveThreadFromInstanceOrId($threadOrThreadId);
        return $thread->modifyLabels($addLabelIds, $removeLabelIds, $optParams);
    }

    /**
     * Add labels to a thread
     *
     * @param GmailThread|string $threadOrThreadId
     * @param string|array $labelIds
     * @param array $optParams
     *
     * @return GmailThread
     * @throws \Google\Service\Exception
     */
    public function addLabels(
        GmailThread|string $threadOrThreadId,
        string|array $labelIds = [],
        array $optParams = []
    ) {
        return $this->modifyLabels($threadOrThreadId, $labelIds, [], $optParams);
    }

    /**
     * Add labels to a thread
     *
     * @param GmailThread|string $threadOrThreadId
     * @param string|array $labelIds
     * @param array $optParams
     *
     * @return GmailThread
     * @throws \Google\Service\Exception
     */
    public function removeLabels(
        GmailThread|string $threadOrThreadId,
        string|array $labelIds = [],
        array $optParams = []
    ) {
        return $this->modifyLabels($threadOrThreadId, [], $labelIds, $optParams);
    }

    /**
     * Send a thread to trash
     *
     * @param GmailThread|string $threadOrThreadId
     * @param array $optParams
     *
     * @return GmailThread
     * @throws \Google\Service\Exception
     */
    public function trash(GmailThread|string $threadOrThreadId, array $optParams = [])
    {
        $thread = $this->resolveThreadFromInstanceOrId($threadOrThreadId);
        return $thread->trash($optParams);
    }

    /**
     * Remove a thread from trash
     *
     * @param GmailThread|string $threadOrThreadId
     * @param array $optParams
     *
     * @return GmailThread
     * @throws \Google\Service\Exception
     */
    public function untrash(GmailThread|string $threadOrThreadId, array $optParams = [])
    {
        $thread = $this->resolveThreadFromInstanceOrId($threadOrThreadId);
        return $thread->untrash($optParams);
    }

    /**
     * Permanently delete a thread
     * Full mailbox permission scopes needed to execute this action.
     * For more info: https://developers.google.com/gmail/api/auth/scopes
     *
     * @param GmailThread|string $threadOrThreadId
     * @param array $optParams
     *
     * @return void
     * @throws \Google\Service\Exception
     */
    public function delete(GmailThread|string $threadOrThreadId, array $optParams = [])
    {
        $thread = $this->resolveThreadFromInstanceOrId($threadOrThreadId);
        $thread->delete($optParams);
    }

    /**
     * List threads request to gmail
     *
     * @param array $optParams
     *
     * @return \Google_Service_Gmail_ListThreadsResponse
     * @throws \Google\Service\Exception
     */
    protected function getGmailThreadListResponse($optParams = [])
    {
        return $this->service->users_threads->listUsersThreads('me', $optParams);
    }

    /**
     * Get thread request to gmail
     *
     * @param string $id
     * @return \Google_Service_Gmail_Thread
     */
    protected function getGmailThreadResponse($id)
    {
        return $this->service->users_threads->get('me', $id);
    }

    /**
     * Get paginated list response
     *
     * @param string|null $currentPageToken
     * @return GmailThreadsList
     */
    protected function getPaginatedListResponse($currentPageToken = null)
    {
        $this->setFilterParam('currentPageToken', $currentPageToken);

        $optParams = $this->prepareFilterParams();
        $response = $this->getGmailThreadListResponse($optParams);

        if ($nextPageToken = $response->getNextPageToken()) {
            $this->setFilterParam('nextPageToken', $nextPageToken);
        } else {
            $this->setFilterParam('nextPageToken', null);
        }

        $estimatedThreadsCount = $response->getResultSizeEstimate();
        $threads = $response->getThreads();

        if (!$threads || !is_array($threads) || !count($threads)) {
            return new GmailThreadsList($this, [], $estimatedThreadsCount);
        }
        $processedThreads = array_map(
            fn($thread) => new GmailThread($thread, $this->client),
            array_values($this->getThreadDetailsOnBatch($threads))
        );

        return new GmailThreadsList($this, $processedThreads, $estimatedThreadsCount);
    }

    /**
     * From list threads response, get detailed response of every threads
     *
     * @param array<\Google_Service_Gmail_Thread> $threads
     * @return array<\Google_Service_Gmail_Thread>
     */
    protected function getThreadDetailsOnBatch($threads)
    {
        $this->client->setUseBatch(true);
        $batch = $this->service->createBatch();
        foreach ($threads as $thread) {
            // @phpstan-ignore-next-line
            $batch->add($this->getGmailThreadResponse($thread->getId()));
        }

        return $batch->execute();
    }

    /**
     * Resolve thread from instance or id
     *
     * @param GmailThread|string $threadOrThreadId
     *
     * @return GmailThread
     * @throws \Google\Service\Exception
     */
    private function resolveThreadFromInstanceOrId($threadOrThreadId)
    {
        if ($threadOrThreadId instanceof GmailThread) {
            return $threadOrThreadId;
        }
        return $this->get($threadOrThreadId);
    }
}
