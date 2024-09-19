<?php
namespace Skn036\Gmail\Message;

class GmailMessageAttachment
{
    /**
     * Attachment MessagePart or id of the attachment
     * @var \Google_Service_Gmail_MessagePart|string
     */
    protected $partOrId;

    /**
     * Id of the message
     * @var string
     */
    protected $messageId;

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
     * @var string
     */
    public $data;

    /**
     * Attachment constructor.
     *
     * @param \Google_Service_Gmail_MessagePart|string $partOrId;
     * @param string $messageId
     */
    public function __construct(
        \Google_Service_Gmail_MessagePart|string $partOrId,
        string $messageId
    ) {
        $this->partOrId = $partOrId;

        if ($partOrId instanceof \Google_Service_Gmail_MessagePart) {
            $this->resolveAttachmentFromPart($partOrId);
        } else {
            $this->id = $partOrId;
        }

        $this->messageId = $messageId;
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
}
