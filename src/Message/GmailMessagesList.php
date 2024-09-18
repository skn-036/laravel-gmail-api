<?php
namespace Skn036\Gmail\Message;

use Illuminate\Support\Collection;
use Skn036\Gmail\Message\MessageResponse;
use Skn036\Gmail\Message\GmailMessage;

class GmailMessagesList
{
    /**
     * Message from gmail
     * @var Collection<int, GmailMessage>
     */
    public $messages;

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
     * Estimated messages count in gmail
     * @var int|string
     */
    public $total;

    /**
     * Gmail fetch service
     * @var MessageResponse
     */
    private $response;

    /**
     * Summary of __construct
     *
     * @param Collection<GmailMessage>|array<GmailMessage> $messages
     * @param MessageResponse $response
     * @param int|string $estimatedDocumentCount
     */
    public function __construct($response, $messages = [], $estimatedDocumentCount = 0)
    {
        $this->messages = collect($messages);
        $this->response = $response;
        $this->total = $estimatedDocumentCount;

        $this->hasNextPage = $this->response->hasNextPage();
        $this->currentPageToken = $this->response->getCurrentPageToken();
        $this->nextPageToken = $this->response->getNextPageToken();
    }

    /**
     * Fetch the messages from next page
     * @return \Skn036\Gmail\Message\GmailMessagesList
     */
    public function next()
    {
        return $this->response->next();
    }
}
