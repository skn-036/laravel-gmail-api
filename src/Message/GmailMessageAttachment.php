<?php
namespace Skn036\Gmail\Message;

use Skn036\Gmail\Gmail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Skn036\Gmail\Facades\Gmail as GmailFacade;

class GmailMessageAttachment
{
    /**
     * Attachment MessagePart or id of the attachment
     * @var \Google_Service_Gmail_MessagePart
     */
    protected $part;

    /**
     * Id of the message
     * @var string
     */
    protected $messageId;

    /**
     * Gmail client
     * @var Gmail|GmailFacade|null
     */
    private $client;

    /**
     * Attachment Id
     * @var string
     */
    public $id;

    /**
     * Attachment filename
     * @var string
     */
    public $filename;

    /**
     * Attachment mimeType
     * @var string
     */
    public $mimeType;

    /**
     * Attachment size
     * @var int
     */
    public $size;

    /**
     * Attachment data
     * @var string|null
     */
    public $data;

    /**
     * Attachment constructor.
     *
     * @param \Google_Service_Gmail_MessagePart $part;
     * @param string $messageId
     * @param Gmail|GmailFacade|null $client
     */
    public function __construct(
        \Google_Service_Gmail_MessagePart $part,
        string $messageId,
        Gmail|GmailFacade|null $client = null
    ) {
        $this->part = $part;
        $this->client = $client;
        $this->messageId = $messageId;

        $this->resolveAttachmentFromPart($part);
    }

    /**
     * Resolve attachment from part
     *
     * @param \Google_Service_Gmail_MessagePart $part
     * @return static
     */
    protected function resolveAttachmentFromPart($part)
    {
        $this->id = $part->getBody()->getAttachmentId();
        $this->filename = $part->getFilename();
        $this->mimeType = $part->getMimeType();
        $this->size = $part->getBody()->getSize();
        $this->data = $part->getBody()->getData();

        return $this;
    }

    /**
     * Get attachment content as base64
     *
     * @param array $optParams
     *
     * @throws \Exception
     * @return static
     */
    public function getData(array $optParams = [])
    {
        if (!$this->client) {
            throw new \Exception('Gmail client is not passed as a parameter');
        }
        if ($this->data) {
            return $this;
        }

        $service = $this->client->initiateService();
        $part = $service->users_messages_attachments->get(
            'me',
            $this->messageId,
            $this->id,
            $optParams
        );
        if ($part->data) {
            $this->data = $part->data;
        }

        return $this;
    }

    /**
     * Decode base64 data
     *
     * @param string $data
     * @return string
     */
    public function decodeBase64($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Download attachment
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download()
    {
        if (!$this->data) {
            $this->getData();
        }
        $content = $this->decodeBase64($this->data);
        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            $this->filename,
            [
                'Content-Type' => $this->mimeType,
                'Content-Length' => $this->size,
                'Content-Disposition' => 'attachment; filename="' . $this->filename . '"',
            ]
        );
    }

    /**
     * Save attachment to the filesystem
     *
     * @param string $path
     * @return string
     */
    public function save(string $path = '')
    {
        if (!$this->data) {
            $this->getData();
        }
        if (!$path) {
            $pathPrefix = config('google.gmail.attachment_path') ?: 'gmail-attachments';
            $userId = $this->client->userId;
            if ($userId) {
                $pathPrefix = $pathPrefix . '/' . $userId;
            }
            $path = $pathPrefix . '/' . $this->messageId . '/' . $this->filename;
        } else {
            $path = $path . '/' . $this->filename;
        }

        $content = $this->decodeBase64($this->data);
        Storage::put($path, $content);

        return $path;
    }
}
