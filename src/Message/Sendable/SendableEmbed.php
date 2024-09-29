<?php
namespace Skn036\Gmail\Message\Sendable;

use Illuminate\Http\UploadedFile;

class SendableEmbed
{
    /**
     * name of the embed
     * for embed to work properly, this name should be used in the body of the email
     * <img src="cid:{name}"> or <div background="cid:{name}"> ... </div>
     *
     * @var string
     */
    public $name = '';

    /**
     * Full path of the given file
     * @var string
     */
    public $fullPath;

    /**
     * storage path of the file if given
     * @var string|null
     */
    public $storagePath;

    /**
     * uploaded file if given
     * @var UploadedFile|null
     */
    public $file;

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
        if ($fileOrPath instanceof UploadedFile) {
            $this->file = $fileOrPath;
            $this->fullPath = $fileOrPath->getPathname();
        } else {
            $this->storagePath = $fileOrPath;
            $this->fullPath = storage_path($fileOrPath);
        }
        $this->name = $name;
    }
}
