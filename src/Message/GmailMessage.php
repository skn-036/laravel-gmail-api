<?php
namespace Skn036\Gmail\Message;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Skn036\Gmail\Draft\Sendable\Draft;
use Skn036\Gmail\Message\Sendable\Email;
use Skn036\Gmail\Message\Traits\ExtractMessage;
use Skn036\Gmail\Gmail;
use Skn036\Gmail\Facades\Gmail as GmailFacade;

class GmailMessage
{
    use ExtractMessage;

    /**
     * Message from gmail
     * @var \Google_Service_Gmail_Message|\Google\Service\Gmail\Message
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
     * @var Collection<GmailMessageAttachment>
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
     * @param \Google_Service_Gmail_Message|\Google\Service\Gmail\Message $message
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

        $this->gmailMessageToParams();
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
     * Creates a replying/forwarding draft instance of the message setting proper headers, subject and thread id.
     * This will not set the "attachments", "to", "cc", "body" of the message.
     * This is because, most of the time forwarded message will be edited on the user interface before sending.
     * So it should be more appropriate to set these values by the public api provided on \Skn036\Gmail\Draft\Sendable\Draft.
     *
     * @return Draft
     */
    public function createDraft()
    {
        return (new Draft($this->client, null, $this))->withReplyOrForward();
    }

    /**
     * Downloads the given attachment
     * @param string $attachmentId
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \Exception
     */
    public function downloadAttachment(string $attachmentId)
    {
        $attachment = $this->attachments->first(
            fn($attachment) => $attachment->id === $attachmentId
        );
        if (!$attachment) {
            throw new \Exception('Attachment not found');
        }

        return $attachment->download();
    }

    /**
     * Saves all attachments on the default disc in filesystem
     * if path is not given, it will save on the gmail.attachment_path on google config
     *
     * @param string $attachmentId
     * @param string $path
     *
     * @return string
     * @throws \Exception
     */
    public function saveAttachment(string $attachmentId, string $path = '')
    {
        $attachment = $this->attachments->first(
            fn($attachment) => $attachment->id === $attachmentId
        );
        if (!$attachment) {
            throw new \Exception('Attachment not found');
        }

        return $attachment->save($path);
    }

    /**
     * Saves all attachments on the default disc in filesystem
     * if path is not given, it will save on the gmail.attachment_path on google config
     *
     * @param string $path
     * @return string[]
     */
    public function saveAllAttachments(string $path = '')
    {
        return $this->attachments
            ->map(fn($attachment) => $attachment->save($path))
            ->values()
            ->all();
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

        $modify = new \Google_Service_Gmail_ModifyMessageRequest();
        if (count($addedLabelIds) > 0) {
            $modify->setAddLabelIds($addedLabelIds);
        }
        if (count($removedLabelIds) > 0) {
            $modify->setRemoveLabelIds($removedLabelIds);
        }

        $service = $this->client->initiateService();
        $message = $service->users_messages->modify('me', $this->id, $modify, $optParams);
        $this->setLabels($message->getLabelIds());

        return $this;
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
        $message = $service->users_messages->trash('me', $this->id, $optParams);
        $this->setLabels($message->getLabelIds());
        return $this;
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
        $message = $service->users_messages->untrash('me', $this->id, $optParams);
        $this->setLabels($message->getLabelIds());
        return $this;
    }

    /**
     * Permanently deletes the message
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
        $service->users_messages->delete('me', $this->id, $optParams);
    }

    /**
     * Get the raw message
     * @return \Google_Service_Gmail_Message|\Google\Service\Gmail\Message
     */
    public function getRawMessage()
    {
        return $this->message;
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
     *
     * @param array<string> $labelIds
     * @return static
     */
    protected function setLabels($labelIds = [])
    {
        if (empty($labelIds)) {
            $labelIds = $this->message->getLabelIds();
        }
        $this->labels = collect(array_values($labelIds ?: []));
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
        $this->attachments = $this->parseAttachments(
            $this->allPartsIncludingNested,
            $this->id,
            $this->client
        );
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
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader($name)
    {
        return $this->getHeaderValue($name, $this->rawHeaders);
    }

    /**
     * Extracts the message from gmail and sets the params
     * @return static
     */
    private function gmailMessageToParams()
    {
        if (!$this->message) {
            return $this;
        }

        $payload = $this->message->getPayload();
        $this->rawHeaders = $this->getPartHeaders($payload);

        $this->allPartsIncludingNested = $this->getFlatPartsCollection(
            collect($payload && $payload->getParts() ? $payload->getParts() : []),
            collect([])
        );
        $this->setParams();

        return $this;
    }

    /**
     * Set all params
     * @return static
     */
    private function setParams()
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
}
