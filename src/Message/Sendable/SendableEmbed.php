<?php
namespace Skn036\Gmail\Message\Sendable;

use Illuminate\Http\UploadedFile;

class SendableEmbed extends SendableAttachment
{
    /**
     * name of the embed
     * for embed to work properly, this name should be used in the body of the email
     * <img src="cid:{name}"> or <div background="cid:{name}"> ... </div>
     *
     * @var string
     */
    public $name;

    /**
     * Summary of __construct
     *
     * @param string|UploadedFile $fileOrPath
     * @param string $name
     *
     * for embed to work properly, this name should be used in the body of the email
     * <img src="cid:{name}"> or <div background="cid:{name}"> ... </div>
     */
    public function __construct(string|UploadedFile $fileOrPath, string $name)
    {
        parent::__construct($fileOrPath);
        $this->name = $name;
    }
}
