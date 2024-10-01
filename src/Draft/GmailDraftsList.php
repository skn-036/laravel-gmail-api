<?php
namespace Skn036\Gmail\Draft;

use Illuminate\Support\Collection;

class GmailDraftsList
{
    /**
     * Drafts from gmail
     * @var Collection<GmailDraft>|array<GmailDraft>
     */
    public $drafts;

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
     * Estimated drafts count in gmail
     * @var int|string
     */
    public $total;

    /**
     * Gmail fetch service
     * @var GmailDraftResponse
     */
    private $response;

    /**
     * Summary of __construct
     *
     * @param Collection<GmailDraft>|array<GmailDraft> $drafts
     * @param GmailDraftResponse $response
     * @param int|string $estimatedDocumentCount
     */
    public function __construct(
        GmailDraftResponse $response,
        Collection|array $drafts = [],
        string|int $estimatedDocumentCount = 0
    ) {
        $this->drafts = collect($drafts);
        $this->response = $response;
        $this->total = $estimatedDocumentCount;

        $this->hasNextPage = $this->response->hasNextPage();
        $this->currentPageToken = $this->response->getCurrentPageToken();
        $this->nextPageToken = $this->response->getNextPageToken();
    }

    /**
     * Fetch the drafts from next page
     * @return \Skn036\Gmail\Draft\GmailDraftsList
     */
    public function next()
    {
        return $this->response->next();
    }
}
