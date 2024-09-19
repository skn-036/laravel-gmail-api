<?php
namespace Skn036\Gmail\Message;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Skn036\Gmail\Message\Sendable\Email;
use Skn036\Gmail\Message\Traits\ExtractMessage;
use Skn036\Gmail\Gmail;
use Skn036\Gmail\Facades\Gmail as GmailFacade;

class GmailMessage
{
    use ExtractMessage;

    /**
     * Message from gmail
     * @var \Google_Service_Gmail_Message
     */
    protected $message;

    /**
     * Gmail Client
     * @var Gmail|GmailFacade
     */
    private $client;

    /**
     * Summary of rawHeaders
     * @var Collection<\Google_Service_Gmail_MessagePartHeader>
     */
    protected $rawHeaders;

    /**
     * All parts are flatten to single collection including multi-level nested parts
     * @var Collection<\Google_Service_Gmail_MessagePart>
     */
    protected $allPartsIncludingNested;

    /**
     * Message Id
     * @var string
     */
    public $id;

    /**
     * Thread id
     * @var string
     */
    public $threadId;

    /**
     * Message header id
     * @var string
     */
    public $headerMessageId;

    /**
     * headerMessageId of the message replied on
     * @var string|null
     */
    public $replyTo;

    /**
     * From recipient
     * @var GmailMessageRecipient
     */
    public $from;

    /**
     * To recipients
     * @var Collection<GmailMessageRecipient>
     */
    public $to;

    /**
     * Cc recipients
     * @var Collection<GmailMessageRecipient>
     */
    public $cc;

    /**
     * Bcc recipients
     * @var Collection<GmailMessageRecipient>
     */
    protected $bcc;

    /**
     * Message Labels
     * @var Collection<string>
     */
    public $labels;

    /**
     * Message subject
     * @var string
     */
    public $subject;

    /**
     * Message date
     * @var Carbon
     */
    public $date;

    /**
     * Message text body
     * @var string
     */
    protected $textBody = '';

    /**
     * Message html body
     * @var string
     */
    protected $htmlBody = '';

    /**
     * Message body
     * @var string
     */
    public $body = '';

    /**
     * Message snippet
     * @var string
     */
    public $snippet;

    /**
     * Message attachments
     * @var Collection
     */
    public $attachments;

    /**
     * Message history id
     * @var string
     */
    public $historyId;

    /**
     * References headers
     */
    public $references;

    /**
     * Summary of __construct
     * @param \Google_Service_Gmail_Message $message
     * @param Gmail|GmailFacade $client
     *
     * @throws \Exception
     */
    public function __construct($message, Gmail|GmailFacade $client)
    {
        if ($message instanceof \Exception) {
            throw $message;
        }
        if (!($message instanceof \Google_Service_Gmail_Message)) {
            throw new \Exception('message is not instance of \Google_Service_Gmail_Message');
        }

        $this->client = $client;
        $this->message = $message;
        $payload = $this->message->getPayload();
        $this->rawHeaders = $this->getPartHeaders($payload);

        $this->allPartsIncludingNested = $this->getFlatPartsCollection(
            collect($payload && $payload->getParts() ? $payload->getParts() : []),
            collect([])
        );
        $this->setParams();
    }

    /**
     * Set all params
     * @return static
     */
    protected function setParams()
    {
        $this->setId()
            ->setThreadId()
            ->setHeaderMessageId()
            ->setReplyTo()
            ->setFrom()
            ->setTo()
            ->setCc()
            ->setBcc()
            ->setLabels()
            ->setSubject()
            ->setDate()
            ->setBody()
            ->setSnippet()
            ->setAttachments()
            ->setHistoryId()
            ->setReferences();

        return $this;
    }

    /**
     * Get the html body of the message part
     * @return string
     */
    public function getHtmlBody()
    {
        return $this->htmlBody;
    }

    /**
     * Get the text body of the message part
     * @return string
     */
    public function getTextBody()
    {
        return $this->textBody;
    }

    /**
     * By default bcc will not be available on public properties of the message
     * if needed this function should be called
     *
     * @return Collection<GmailMessageRecipient>
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * Creates a replyable instance of the message setting proper headers, subject and thread id.
     * This will not set the "to", "cc", "body" of the message.
     * This is because, most of the time replied message will be edited on the user interface before sending.
     * So it should be more appropriate to set these values by the public api provided on \Skn036\Gmail\Message\Sendable\Email.
     *
     * @return Email
     */
    public function createReply()
    {
        return (new Email($this->client, $this))->createReply();
    }

    /**
     * Creates a forwarding instance of the message setting proper headers, subject and thread id.
     * This will not set the "attachments", "to", "cc", "body" of the message.
     * This is because, most of the time forwarded message will be edited on the user interface before sending.
     * So it should be more appropriate to set these values by the public api provided on \Skn036\Gmail\Message\Sendable\Email.
     *
     * @return Email
     */
    public function createForward()
    {
        return (new Email($this->client, $this))->createForward();
    }

    /**
     * Sets the id of the message
     * @return static
     */
    protected function setId()
    {
        $this->id = $this->message->getId();
        return $this;
    }

    /**
     * Sets the thread id of the message
     * @return static
     */
    protected function setThreadId()
    {
        $this->threadId = $this->message->getThreadId();
        return $this;
    }

    /**
     * Sets the header message id of the message
     * @return static
     */
    protected function setHeaderMessageId()
    {
        $this->headerMessageId = $this->getHeader('message-id');
        return $this;
    }

    /**
     * Sets the reply to of the message
     * @return static
     */
    protected function setReplyTo()
    {
        $this->replyTo = $this->getHeader('in-reply-to');
        return $this;
    }

    /**
     * Sets the from of the message
     * @return static
     */
    protected function setFrom()
    {
        $fromStr = $this->getHeader('from') ?: '';
        $recipients = $this->parseRecipients($fromStr);
        if ($recipients->count() > 0) {
            $this->from = $recipients->first();
        } else {
            $this->from = new GmailMessageRecipient($fromStr);
        }
        return $this;
    }

    /**
     * Sets the to of the message
     * @return static
     */
    protected function setTo()
    {
        $this->to = $this->parseRecipients($this->getHeader('to') ?: '');
        return $this;
    }

    /**
     * Sets the cc of the message
     * @return static
     */
    protected function setCc()
    {
        $this->cc = $this->parseRecipients($this->getHeader('cc') ?: '');
        return $this;
    }

    /**
     * Sets the bcc of the message
     * @return static
     */
    protected function setBcc()
    {
        $this->bcc = $this->parseRecipients($this->getHeader('bcc') ?: '');
        return $this;
    }

    /**
     * Sets the labels of the message
     * @return static
     */
    protected function setLabels()
    {
        $this->labels = collect(array_values($this->message->getLabelIds() ?: []));
        return $this;
    }

    /**
     * Sets the subject of the message
     * @return static
     */
    protected function setSubject()
    {
        $this->subject = $this->getHeader('subject') ?: '';
        return $this;
    }

    /**
     * Sets the date of the message
     * @return static
     */
    protected function setDate()
    {
        $date = $this->getHeader('date');
        $date = preg_replace('/\([^)]+\)/', '', $date);
        $this->date = Carbon::parse(preg_replace('/\s+/', ' ', $date));
        return $this;
    }

    /**
     * Sets the body of the message
     * @return static
     */
    protected function setBody()
    {
        $this->textBody = $this->getBodyByContentType('text/plain', $this->allPartsIncludingNested);
        $this->htmlBody = $this->getBodyByContentType('text/html', $this->allPartsIncludingNested);

        $this->body = $this->htmlBody ?: $this->textBody;
        return $this;
    }

    /**
     * Sets the snippet of the message
     * @return static
     */
    protected function setSnippet()
    {
        $this->snippet = $this->message->getSnippet();
        return $this;
    }

    /**
     * Sets the attachments of the message
     * @return static
     */
    protected function setAttachments()
    {
        $this->attachments = $this->parseAttachments($this->allPartsIncludingNested, $this->id);
        return $this;
    }

    /**
     * Sets the history id of the message
     * @return static
     */
    protected function setHistoryId()
    {
        $this->historyId = $this->message->getHistoryId();
        return $this;
    }

    /**
     * Sets the references from header in the message
     * @return static
     */
    protected function setReferences()
    {
        $this->references = $this->getHeader('references');
        return $this;
    }

    /**
     * get header by name
     * @param string $name
     * @return string|null
     */
    protected function getHeader($name)
    {
        return $this->getHeaderValue($name, $this->rawHeaders);
    }
}
