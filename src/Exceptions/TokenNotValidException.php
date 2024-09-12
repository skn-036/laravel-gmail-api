<?php
namespace Skn036\Gmail\Exceptions;

class TokenNotValidException extends \Exception
{
    const DEFAULT_MESSAGE = 'Google client is not authenticated. Please login again.';
    /**
     * Summary of __construct
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = '', $code = 0, $previous = null)
    {
        parent::__construct($message ?: static::DEFAULT_MESSAGE, $code, $previous);
    }
}
