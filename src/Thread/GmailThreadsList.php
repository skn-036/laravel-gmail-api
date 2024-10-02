<?php
namespace Skn036\Gmail\Thread;

use Illuminate\Support\Collection;
use Skn036\Gmail\Thread\GmailThreadResponse;
use Skn036\Gmail\Thread\GmailThread;

class GmailThreadsList
{
    /**
     * Threads from gmail
     * @var Collection<GmailThread>|array<GmailThread>
     */
    public $threads;

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
     * Estimated threads count in gmail
     * @var int|string
     */
    public $total;

    /**
     * Gmail fetch service
     * @var GmailThreadResponse
     */
    private $response;

    /**
     * Summary of __construct
     *
     * @param Collection<GmailThread>|array<GmailThread> $threads
     * @param GmailThreadResponse $response
     * @param int|string $estimatedDocumentCount
     */
    public function __construct(
        GmailThreadResponse $response,
        Collection|array $threads = [],
        string|int $estimatedDocumentCount = 0
    ) {
        $this->threads = collect($threads);
        $this->response = $response;
        $this->total = $estimatedDocumentCount;

        $this->hasNextPage = $this->response->hasNextPage();
        $this->currentPageToken = $this->response->getCurrentPageToken();
        $this->nextPageToken = $this->response->getNextPageToken();
    }

    /**
     * Fetch the threads from next page
     * @return \Skn036\Gmail\Thread\GmailThreadsList
     */
    public function next()
    {
        return $this->response->next();
    }
}
