<?php
namespace Skn036\Gmail\Message\Traits;

use Illuminate\Support\Collection;
use Skn036\Gmail\Message\GmailMessageRecipient;
use Skn036\Gmail\Message\GmailMessageAttachment;
use Skn036\Gmail\Gmail;
use Skn036\Gmail\Facades\Gmail as GmailFacade;

trait ExtractMessage
{
    /**
     * get all headers of a message part
     *
     * @param \Google_Service_Gmail_MessagePart|null $part
     * @return Collection<\Google_Service_Gmail_MessagePartHeader>
     */
    protected function getPartHeaders($part)
    {
        if (!$part) {
            return collect([]);
        }
        return collect($part->getHeaders());
    }

    /**
     * get header by name from given headers
     * @param string $name
     * @param Collection<\Google_Service_Gmail_MessagePartHeader>|null $headers
     * @return string|null
     */
    protected function getHeaderValue($name, $headers)
    {
        $name = strtolower($name);
        $header = $headers->first(fn($header) => strtolower($header->getName()) === $name);
        if (!$header) {
            return null;
        }
        return $header->getValue();
    }

    /**
     * parse recipients from a string
     * @param string $str
     * @return Collection<GmailMessageRecipient>
     */
    protected function parseRecipients($str)
    {
        $pattern = '/(?:"?([^"]*)"?\s)?(?:<?(.+@[^>]+)>?)/';
        $results = collect([]);

        $arr = explode(',', $str);
        foreach ($arr as $recipient) {
            $recipient = trim($recipient);
            preg_match_all($pattern, $recipient, $matches, PREG_SET_ORDER);

            if ($matches) {
                foreach ($matches as $match) {
                    $name = $match[1] ?: '';
                    $email = $match[2] ?: '';

                    $results->push(new GmailMessageRecipient($email, $name));
                }
            }
        }

        return $results;
    }

    /**
     * parse attachments from a message part
     * @param Collection<\Google_Service_Gmail_MessagePart> $parts
     * @param string $messageId
     * @param Gmail|GmailFacade|null $client
     *
     * @return Collection<GmailMessageAttachment>
     */
    protected function parseAttachments($parts, $messageId, $client)
    {
        return $parts
            ->filter(fn($part) => $part->getFilename() && $part->getBody()->getAttachmentId())
            ->map(fn($part) => new GmailMessageAttachment($part, $messageId, $client))
            ->values();
    }

    /**
     * Flatten deep nested parts of the message in a single collection
     *
     * @param Collection<\Google_Service_Gmail_MessagePart> $parts
     * @param Collection<\Google_Service_Gmail_MessagePart> $acc
     *
     * @return Collection<\Google_Service_Gmail_MessagePart>
     */
    protected function getFlatPartsCollection($parts, $acc)
    {
        if ($parts->count() === 0) {
            return $acc;
        }
        foreach ($parts as $part) {
            if (!$part) {
                continue;
            }
            $acc = $acc->push($part);
            $childParts = collect($part->getParts() ?: []);
            if ($childParts->count() > 0) {
                $acc = $this->getFlatPartsCollection($childParts, $acc);
            }
        }
        return $acc;
    }

    /**
     * get raw body data from given content type
     *
     * @param string $contentType
     * @param Collection<\Google_Service_Gmail_MessagePart|null> $flatPartsCollection
     *
     * @return string
     */
    protected function getBodyByContentType($contentType, $flatPartsCollection)
    {
        $part = $flatPartsCollection->first(function ($part) use ($contentType) {
            if (!$part) {
                return false;
            }
            $headers = collect($part->getHeaders());
            $contentTypeHeader = $this->getHeaderValue('content-type', $headers);
            if (!$contentTypeHeader) {
                return false;
            }
            return strpos($contentTypeHeader, $contentType) !== false;
        });

        if (!$part) {
            return '';
        }
        return base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
    }
}
