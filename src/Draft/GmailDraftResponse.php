<?php
namespace Skn036\Gmail\Draft;

use Skn036\Gmail\Gmail;
use Skn036\Gmail\Filters\GmailFilter;
use Skn036\Gmail\Draft\Sendable\Draft;
use Skn036\Gmail\Facades\Gmail as GmailFacade;
use Skn036\Gmail\Exceptions\TokenNotValidException;

class GmailDraftResponse extends GmailFilter
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
     * Create a new Gmail draft response instance.
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
     * List drafts
     *
     * @param string|null $pageToken
     *
     * @return GmailDraftsList
     * @throws \Google\Service\Exception
     */
    public function list(string|null $pageToken = null)
    {
        return $this->getPaginatedListResponse($pageToken);
    }

    /**
     * Fetch drafts from next page
     *
     * @return GmailDraftsList
     * @throws \Google\Service\Exception
     */
    public function next()
    {
        if ($this->hasNextPage()) {
            return $this->getPaginatedListResponse($this->nextPageToken);
        } else {
            return new GmailDraftsList($this, []);
        }
    }

    /**
     * Get draft by id
     *
     * @param string $id
     *
     * @return GmailDraft
     * @throws \Google\Service\Exception
     */
    public function get(string $id)
    {
        $draft = $this->getGmailDraftResponse($id);
        return new GmailDraft($draft, $this->client);
    }

    /**
     * Creates a new sendable draft instance
     * @return Draft
     */
    public function create()
    {
        return new Draft($this->client);
    }

    /**
     * Creates a new updatable draft instance
     *
     * @param GmailDraft|string $draftOrDraftId
     *
     * @return Draft
     */
    public function edit(GmailDraft|string $draftOrDraftId)
    {
        $draftId = $draftOrDraftId instanceof GmailDraft ? $draftOrDraftId->id : $draftOrDraftId;
        $draft = $this->get($draftId);
        return (new Draft($this->client, $draft))->hydrateDraft();
    }

    /**
     * Deletes the draft
     *
     * @param GmailDraft|string $draftOrDraftId
     * @param array $optParams
     *
     * @return void
     */
    public function delete(GmailDraft|string $draftOrDraftId, array $optParams = [])
    {
        $draftId = $draftOrDraftId instanceof GmailDraft ? $draftOrDraftId->id : $draftOrDraftId;
        $this->service->users_drafts->delete('me', $draftId, $optParams);
    }

    /**
     * List drafts request to gmail
     *
     * @param array $optParams
     *
     * @return \Google_Service_Gmail_ListDraftsResponse
     * @throws \Google\Service\Exception
     */
    protected function getGmailDraftListResponse($optParams = [])
    {
        return $this->service->users_drafts->listUsersDrafts('me', $optParams);
    }

    /**
     * Get draft request to gmail
     *
     * @param string $id
     * @return \Google_Service_Gmail_Draft
     */
    protected function getGmailDraftResponse($id)
    {
        return $this->service->users_drafts->get('me', $id);
    }

    /**
     * Get paginated list response
     *
     * @param string|null $currentPageToken
     * @return GmailDraftsList
     */
    protected function getPaginatedListResponse($currentPageToken = null)
    {
        $this->setFilterParam('currentPageToken', $currentPageToken);

        $optParams = $this->prepareFilterParams();
        $response = $this->getGmailDraftListResponse($optParams);

        if ($nextPageToken = $response->getNextPageToken()) {
            $this->setFilterParam('nextPageToken', $nextPageToken);
        } else {
            $this->setFilterParam('nextPageToken', null);
        }

        $estimatedDraftsCount = $response->getResultSizeEstimate();
        $drafts = $response->getDrafts();

        if (!$drafts || !is_array($drafts) || !count($drafts)) {
            return new GmailDraftsList($this, [], $estimatedDraftsCount);
        }
        $processedDrafts = array_map(
            fn($draft) => new GmailDraft($draft, $this->client),
            array_values($this->getDraftDetailsOnBatch($drafts))
        );

        return new GmailDraftsList($this, $processedDrafts, $estimatedDraftsCount);
    }

    /**
     * From list drafts response, get detailed response of every drafts
     *
     * @param array<\Google_Service_Gmail_Draft> $drafts
     * @return array<\Google_Service_Gmail_Draft>
     */
    protected function getDraftDetailsOnBatch($drafts)
    {
        $this->client->setUseBatch(true);
        $batch = $this->service->createBatch();
        foreach ($drafts as $draft) {
            // @phpstan-ignore-next-line
            $batch->add($this->getGmailDraftResponse($draft->getId()));
        }

        return $batch->execute();
    }
}
