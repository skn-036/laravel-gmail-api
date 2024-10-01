<?php
namespace Skn036\Gmail\Message\Sendable;

use Skn036\Gmail\Gmail;
use Illuminate\Mail\Markdown;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Mime\Email;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Symfony\Component\Mime\Address;
use Skn036\Gmail\Message\GmailMessage;
use Skn036\Gmail\Facades\Gmail as GmailFacade;
use Skn036\Gmail\Message\GmailMessageRecipient;
use Skn036\Gmail\Exceptions\TokenNotValidException;

class Sendable
{
    /**
     * Gmail message instance
     * @var GmailMessage|null
     */
    protected $replyToMessage;

    /**
     * Gmail client
     * @var Gmail
     */
    protected $client;

    /**
     * Name of the sender
     * @var string|null
     */
    protected $fromName;

    /**
     * Email of the sender
     * @var string
     */
    protected $fromEmail;

    /**
     * To recipients of the message
     * @var Collection<GmailMessageRecipient>|null
     */
    protected $toRecipients;

    /**
     * Cc recipients of the message
     * @var Collection<GmailMessageRecipient>|null
     */
    protected $ccRecipients;

    /**
     * Bcc recipients of the message
     * @var Collection<GmailMessageRecipient>|null
     */
    protected $bccRecipients;

    /**
     * Subject of the email
     * @var string
     */
    protected $emailSubject = '';

    /**
     * Priority of the email
     * values: 1 for lowest, 2 for low, 3 for normal, 4 for high and 5 for highest
     * @var int
     */
    protected $emailPriority = 3;

    /**
     * Body of the email
     * @var string
     */
    protected $emailBody = '';

    /**
     * Thread id of the message it is being replied to
     * @var string|null
     */
    protected $threadId;

    /**
     * Gmail message headers for reply, reply all and forward
     * @var array
     */
    protected $headers = [];

    /**
     * Attachments of the email
     * @var Collection<SendableAttachment>|null
     */
    protected $emailAttachments;

    /**
     * Embeds of the email
     * @var Collection<SendableEmbed>|null
     */
    protected $emailEmbeds;

    /**
     * Symfony email instance
     * @var Email
     */
    protected $symfonyEmail;

    /**
     * Summary of __construct
     * @param Gmail|GmailFacade $client
     * @param GmailMessage|null $replyToMessage
     *
     * @throws TokenNotValidException
     */
    public function __construct(Gmail|GmailFacade $client, GmailMessage|null $replyToMessage = null)
    {
        $client->throwExceptionIfNotAuthenticated();

        $this->client = $client;
        $this->replyToMessage = $replyToMessage;

        $this->setMyEmail()->setMyName();
    }

    /**
     * Sets name of the email sender
     *
     * @param string|null $name
     * @return static
     */
    public function setMyName(string|null $name = null)
    {
        if ($name) {
            $this->fromName = $name;
        } else {
            $this->fromName = $this->client->profile['name'];
        }
        return $this;
    }

    /**
     * Sets to recipients to the message
     * Multiple calls will override the previous recipients
     *
     * addresses can be given as a string like 'example@example.com'
     * or as an array like ['example@example.com', 'Example Name'] or ['example@example.com']
     *
     * @param string|array|GmailMessageRecipient ...$addresses
     * @return static
     */
    public function to(string|array|GmailMessageRecipient ...$addresses)
    {
        $this->toRecipients = $this->setRecipients($addresses, 'to');
        return $this;
    }

    /**
     * Adds to recipients to the message
     * Multiple calls will add the recipients to the previous recipients
     *
     * addresses can be given as a string like 'example@example.com'
     * or as an array like ['example@example.com', 'Example Name'] or ['example@example.com']
     *
     * @param string|array|GmailMessageRecipient ...$addresses
     * @return static
     */
    public function addTo(string|array|GmailMessageRecipient ...$addresses)
    {
        $this->toRecipients = $this->setRecipients($addresses, 'to', $this->toRecipients);
        return $this;
    }

    /**
     * Returns the to recipients of the message
     *
     * @return Collection<GmailMessageRecipient>|null
     */
    public function getTo()
    {
        return $this->toRecipients;
    }

    /**
     * Sets cc recipients to the message
     * Multiple calls will override the previous recipients
     *
     * addresses can be given as a string like 'example@example.com'
     * or as an array like ['example@example.com', 'Example Name'] or ['example@example.com']
     *
     * @param string|array|GmailMessageRecipient ...$addresses
     * @return static
     */
    public function cc(string|array|GmailMessageRecipient ...$addresses)
    {
        $this->ccRecipients = $this->setRecipients($addresses, 'cc');
        return $this;
    }

    /**
     * Adds cc recipients to the message
     * Multiple calls will add the recipients to the previous recipients
     *
     * addresses can be given as a string like 'example@example.com'
     * or as an array like ['example@example.com', 'Example Name'] or ['example@example.com']
     *
     * @param string|array|GmailMessageRecipient ...$addresses
     * @return static
     */
    public function addCc(string|array|GmailMessageRecipient ...$addresses)
    {
        $this->ccRecipients = $this->setRecipients($addresses, 'cc', $this->ccRecipients);
        return $this;
    }

    /**
     * Returns the cc recipients of the message
     *
     * @return Collection<GmailMessageRecipient>|null
     */
    public function getCc()
    {
        return $this->ccRecipients;
    }

    /**
     * Sets bcc recipients to the message
     * Multiple calls will override the previous recipients
     *
     * addresses can be given as a string like 'example@example.com'
     * or as an array like ['example@example.com', 'Example Name'] or ['example@example.com']
     *
     * @param string|array|GmailMessageRecipient ...$addresses
     * @return static
     */
    public function bcc(string|array|GmailMessageRecipient ...$addresses)
    {
        $this->bccRecipients = $this->setRecipients($addresses, 'bcc');
        return $this;
    }

    /**
     * Adds bcc recipients to the message
     * Multiple calls will add the recipients to the previous recipients
     *
     * addresses can be given as a string like 'example@example.com'
     * or as an array like ['example@example.com', 'Example Name'] or ['example@example.com']
     *
     * @param string|array|GmailMessageRecipient ...$addresses
     * @return static
     */
    public function addBcc(string|array|GmailMessageRecipient ...$addresses)
    {
        $this->bccRecipients = $this->setRecipients($addresses, 'bcc', $this->bccRecipients);
        return $this;
    }

    /**
     * Returns the bcc recipients of the message
     *
     * @return Collection<GmailMessageRecipient>|null
     */
    public function getBcc()
    {
        return $this->bccRecipients;
    }

    /**
     * Sets the subject of the email
     *
     * @param string $subject
     * @return static
     */
    public function subject(string $subject)
    {
        $this->emailSubject = $subject;
        return $this;
    }

    /**
     * Returns the subject of the message
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->emailSubject;
    }

    /**
     * Sets the priority of the email
     * values: 1 for lowest, 2 for low, 3 for normal, 4 for high and 5 for highest
     *
     * @param int $priority
     * @return static
     */
    public function priority(int $priority)
    {
        $this->emailPriority = $priority;
        return $this;
    }

    /**
     * Returns the to priority of the message
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->emailPriority;
    }

    /**
     * Generates mail body from laravel view file
     *
     * @param string $view
     * @param array $data
     * @param array $mergeData
     *
     * @return static
     * @throws \Throwable
     */
    public function view(string $view, array $data = [], array $mergeData = [])
    {
        $this->emailBody = view($view, $data, $mergeData)->render();
        return $this;
    }

    /**
     * Generates mail body from markdown file
     *
     * @param string $view
     * @param array $data
     *
     * @return static
     * @throws \Throwable
     */
    public function markdown(string $view, array $data = [])
    {
        $markdown = Container::getInstance()->make(Markdown::class);

        if (config('mail.markdown.theme')) {
            $markdown->theme(config('mail.markdown.theme'));
        }

        $this->emailBody = $markdown->render($view, $data);
        return $this;
    }

    /**
     * Generates mail body from given text or html
     *
     * @param string $emailBody
     *
     * @return static
     */
    public function body(string $emailBody)
    {
        $this->emailBody = $emailBody;
        return $this;
    }

    /**
     * Returns the body of the message
     *
     * @return string
     */
    public function getBody()
    {
        return $this->emailBody;
    }

    /**
     * Returns the headers to be set on the message
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Sets headers to the message
     *
     * @param string $header
     * @param string $value
     *
     * @return static
     */
    public function setHeader($header, $value)
    {
        if (!is_array($this->headers)) {
            $this->headers = [];
        }
        $this->headers[$header] = $value;
        return $this;
    }

    /**
     * Returns the thread id of the replying/forwarding messages
     *
     * @return string|null
     */
    public function getThreadId()
    {
        return $this->threadId;
    }

    /**
     * Attaches files to the email.
     * Multiple calls will override the previous attachments
     * If given as path, it must be given with respect to storage folder
     *
     * @param string|UploadedFile|SendableAttachment ...$uploadedFileOrPaths
     * @return static
     */
    public function attach(string|UploadedFile|SendableAttachment ...$uploadedFileOrPaths)
    {
        $this->emailAttachments = collect([]);
        foreach ($uploadedFileOrPaths as $uploadedFileOrPath) {
            if ($uploadedFileOrPath instanceof SendableAttachment) {
                $this->emailAttachments->push($uploadedFileOrPath);
            } else {
                $this->emailAttachments->push(new SendableAttachment($uploadedFileOrPath));
            }
        }
        return $this;
    }

    /**
     * Add attachments to the email.
     * Multiple calls will append the previous attachments
     * If given as path, it must be given with respect to storage folder
     *
     * @param string|UploadedFile|SendableAttachment ...$uploadedFileOrPaths
     * @return static
     */
    public function addAttachment(string|UploadedFile|SendableAttachment ...$uploadedFileOrPaths)
    {
        if (!$this->emailAttachments) {
            $this->emailAttachments = collect([]);
        }
        foreach ($uploadedFileOrPaths as $uploadedFileOrPath) {
            if ($uploadedFileOrPath instanceof SendableAttachment) {
                $this->emailAttachments->push($uploadedFileOrPath);
            } else {
                $this->emailAttachments->push(new SendableAttachment($uploadedFileOrPath));
            }
        }
        return $this;
    }

    /**
     * Returns the email attachments
     *
     * @return Collection<SendableAttachment>|null
     */
    public function getAttachments()
    {
        return $this->emailAttachments;
    }

    /**
     * Embeds files to the email.
     * Multiple calls will override the previous embeds
     * If given as path, it must be given with respect to storage folder
     * for embed to work properly, this name should be used in the body of the email
     * <img src="cid:{name}"> or <div background="cid:{name}"> ... </div>
     *
     * @param array|SendableEmbed ...$embeds
     * @return static
     */
    public function embed(array|SendableEmbed ...$embeds)
    {
        $this->emailEmbeds = collect([]);
        foreach ($embeds as $embed) {
            if ($embed instanceof SendableEmbed) {
                $this->emailEmbeds->push($embed);
            } else {
                if (!is_array($embed) || count($embed) !== 2) {
                    throw new \InvalidArgumentException(
                        'Embeds must be given as an array with two elements: [path|UploadedFile, name]'
                    );
                }
                $this->emailEmbeds->push(new SendableEmbed(...$embed));
            }
        }
        return $this;
    }

    /**
     * Add embeds files to the email.
     * Multiple calls will append the previous embeds
     * If given as path, it must be given with respect to storage folder
     * for embed to work properly, this name should be used in the body of the email
     * <img src="cid:{name}"> or <div background="cid:{name}"> ... </div>
     *
     * @param array|SendableEmbed ...$embeds
     * @return static
     */
    public function addEmbed(array|SendableEmbed ...$embeds)
    {
        if (!$this->emailEmbeds) {
            $this->emailEmbeds = collect([]);
        }
        foreach ($embeds as $embed) {
            if ($embed instanceof SendableEmbed) {
                $this->emailEmbeds->push($embed);
            } else {
                if (!is_array($embed) || count($embed) !== 2) {
                    throw new \InvalidArgumentException(
                        'Embeds must be given as an array with two elements: [path|UploadedFile, name]'
                    );
                }
                $this->emailEmbeds->push(new SendableEmbed(...$embed));
            }
        }
        return $this;
    }

    /**
     * Returns the email embeds
     *
     * @return Collection<SendableEmbed>|null
     */
    public function getEmbeds()
    {
        return $this->emailEmbeds;
    }

    /**
     * Sets email of the email sender
     *
     * @return static
     */
    private function setMyEmail()
    {
        $this->fromEmail = $this->client->email;
        return $this;
    }

    /**
     * Sets or adds recipient for to, cc, bcc fields
     * Error will be thrown if any of the email addresses are invalid
     *
     * @param array<string|array|GmailMessageRecipient> $addresses
     * @param string $resource
     * @param Collection<GmailMessageRecipient>|null $current
     *
     * @throws \InvalidArgumentException
     *
     * @return Collection<GmailMessageRecipient>
     */
    private function setRecipients($addresses, $resource, $current = null)
    {
        $recipients = $this->convertToMessageRecipients($addresses);

        if (!$this->isAllMessageRecipientsAreValid($recipients)) {
            throw new \InvalidArgumentException(
                "All email addresses are not valid on $resource recipients"
            );
        }

        return $this->mergeRecipients($recipients, $current);
    }

    /**
     * merge recipients of the message on fields like to, cc, bcc
     *
     * @param Collection<GmailMessageRecipient> $recipients
     * @param Collection<GmailMessageRecipient>|null $current

     * @return Collection<GmailMessageRecipient>
     */
    private function mergeRecipients($recipients, $current = null)
    {
        if (!$current) {
            $current = collect([]);
        }
        return $current->merge($recipients);
    }

    /**
     * Convert the recipients to a collection of GmailMessageRecipient instances.
     *
     * @param array $addresses
     * @return Collection<GmailMessageRecipient>
     */
    private function convertToMessageRecipients($addresses)
    {
        return collect($addresses)->map(fn($address) => $this->convertToMessageRecipient($address));
    }

    /**
     * Convert the given recipient to a GmailMessageRecipient instance.
     *
     * @param string|array|GmailMessageRecipient $address
     * @return GmailMessageRecipient
     */
    private function convertToMessageRecipient($address)
    {
        if ($address instanceof GmailMessageRecipient) {
            return $address;
        }
        if (is_array($address)) {
            $name = count($address) > 1 ? $address[1] : null;
            return new GmailMessageRecipient($address[0], $name);
        }
        return new GmailMessageRecipient($address);
    }

    /**
     * Check whether all given recipients are valid.
     *
     * @param Collection<GmailMessageRecipient> $recipients
     * @return bool
     */
    private function isAllMessageRecipientsAreValid($recipients)
    {
        return $recipients->every(fn($recipient) => $recipient->isEmailValid());
    }

    /**
     * Set thread id and proper headers for messages being in same thread
     * @return static
     */
    protected function addMessageToSameThread()
    {
        if (empty($this->replyToMessage->from->email)) {
            throw new \Exception(
                'To create reply/forward on current thread, message must be given on the constructor'
            );
        }
        $this->threadId = $this->replyToMessage->threadId;
        $this->subject($this->replyToMessage->subject);
        $this->headers = [
            'In-Reply-To' => $this->replyToMessage->headerMessageId,
            'References' => $this->replyToMessage->references,
        ];

        return $this;
    }

    /**
     * Add to, cc and bcc fields to the Symfony email instance
     *
     * @param Collection<GmailMessageRecipient>|null $recipients
     * @param string $field
     *
     * @return static
     */
    private function addRecipientsToSymfonyEmail($recipients, $field)
    {
        if ($recipients && $recipients instanceof Collection && $recipients->count()) {
            $recipientsArray = $recipients
                ->map(fn($recipient) => new Address($recipient->email, $recipient->name))
                ->values()
                ->all();
            $this->symfonyEmail->{$field}(...$recipientsArray);
        }
        return $this;
    }

    /**
     * Converts string to base64
     *
     * @param string $data
     * @return string
     */
    private function toBase64($data)
    {
        return rtrim(strtr(base64_encode($data), ['+' => '-', '/' => '_']), '=');
    }

    /**
     * Converts the message to Gmail payload
     *
     * @param \Google_Service_Gmail_Message|\Google\Service\Gmail\Message|null $message
     *
     * @return \Google_Service_Gmail_Message|\Google\Service\Gmail\Message
     */
    protected function setGmailMessageBody($message = null)
    {
        $this->symfonyEmail = (new Email())->from(new Address($this->fromEmail, $this->fromName));
        foreach (
            [
                'to' => $this->toRecipients,
                'cc' => $this->ccRecipients,
                'bcc' => $this->bccRecipients,
            ]
            as $field => $recipients
        ) {
            $this->addRecipientsToSymfonyEmail($recipients, $field);
        }

        $this->symfonyEmail
            ->subject($this->emailSubject)
            ->html($this->emailBody)
            ->priority($this->emailPriority);

        foreach ($this->headers as $header => $value) {
            if ($value) {
                $this->symfonyEmail->getHeaders()->addTextHeader($header, $value);
            }
        }

        if ($this->emailAttachments instanceof Collection && $this->emailAttachments->count()) {
            $this->emailAttachments->each(
                fn($attachment) => $this->symfonyEmail->attachFromPath(
                    $attachment->fullPath,
                    $attachment->name
                )
            );
        }

        if ($this->emailEmbeds instanceof Collection && $this->emailEmbeds->count()) {
            $this->emailEmbeds->each(
                fn($embed) => $this->symfonyEmail->embedFromPath($embed->fullPath, $embed->name)
            );
        }

        $rawMessage = $this->toBase64($this->symfonyEmail->toString());

        if (!$message) {
            $message = new \Google_Service_Gmail_Message();
        }
        if ($this->threadId) {
            $message->setThreadId($this->threadId);
        }
        $message->setRaw($rawMessage);

        return $message;
    }
}
