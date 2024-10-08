<?php
namespace Skn036\Gmail\Message\Sendable;

use Illuminate\Http\UploadedFile;

class SendableAttachment
{
    /**
     * name of the file
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
     */
    public function __construct(string|UploadedFile $fileOrPath)
    {
        if ($fileOrPath instanceof UploadedFile) {
            $this->file = $fileOrPath;
            $this->fullPath = $fileOrPath->getPathname();
            $this->name = $fileOrPath->getClientOriginalName();
        } else {
            $this->storagePath = $fileOrPath;
            $this->fullPath = storage_path($fileOrPath);
            $this->name = basename($fileOrPath);
        }
    }
}
