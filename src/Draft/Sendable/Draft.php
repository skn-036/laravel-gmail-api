<?php
namespace Skn036\Gmail\Draft\Sendable;

use Skn036\Gmail\Gmail;
use Skn036\Gmail\Draft\GmailDraft;
use Skn036\Gmail\Message\GmailMessage;
use Skn036\Gmail\Draft\GmailDraftResponse;
use Skn036\Gmail\Message\Sendable\Sendable;
use Skn036\Gmail\Facades\Gmail as GmailFacade;

class Draft extends Sendable
{
    /**
     * Gmail Draft
     * @var GmailDraft|null
     */
    protected $draft = null;
    /**
     * Summary of __construct
     *
     * @param Gmail|GmailFacade $client
     * @param GmailDraft|null $draft
     * @param GmailMessage|null $replyToMessage
     *
     */
    public function __construct(
        Gmail|GmailFacade $client,
        GmailDraft|null $draft = null,
        GmailMessage|null $replyToMessage = null
    ) {
        $this->draft = $draft;
        parent::__construct($client, $replyToMessage);
    }

    /**
     * Creates a reply or forward thread
     * @return static
     */
    public function withReplyOrForward()
    {
        return $this->addMessageToSameThread();
    }

    /**
     * Creates a new draft
     *
     * @param array $optParams
     *
     * @return GmailDraft
     */
    public function store(array $optParams = [])
    {
        $messageBody = $this->setGmailMessageBody();
        $service = $this->client->initiateService();

        $postBody = new \Google_Service_Gmail_Draft();
        $postBody->setMessage($messageBody);

        $draft = $service->users_drafts->create('me', $postBody, $optParams);
        return (new GmailDraftResponse($this->client))->get($draft->getId());
    }

    /**
     * Updates the draft
     *
     * @param array $optParams
     *
     * @return GmailDraft
     */
    public function update(array $optParams = [])
    {
        if (!$this->draft) {
            throw new \Exception('Draft is required to update');
        }

        $messageBody = $this->setGmailMessageBody();
        $postBody = new \Google_Service_Gmail_Draft();
        $postBody->setMessage($messageBody);
        $postBody->setId($this->draft->id);

        $service = $this->client->initiateService();
        $updatedDraft = $service->users_drafts->update(
            'me',
            $this->draft->id,
            $postBody,
            $optParams
        );

        return (new GmailDraftResponse($this->client))->get($updatedDraft->getId());
    }

    /**
     * Saves the draft
     *
     * @param array $optParams
     *
     * @return GmailDraft
     */
    public function save(array $optParams = [])
    {
        if ($this->draft) {
            return $this->update($optParams);
        }
        return $this->store($optParams);
    }

    /**
     * Sends the draft
     *
     * @param array $optParams
     *
     * @return GmailMessage
     */
    public function send(array $optParams = [])
    {
        $messageBody = $this->setGmailMessageBody();
        $postBody = new \Google_Service_Gmail_Draft();
        $postBody->setMessage($messageBody);

        if ($this->draft) {
            $postBody->setId($this->draft->id);
        }

        $service = $this->client->initiateService();
        $message = $service->users_drafts->send('me', $postBody, $optParams);

        return new GmailMessage(
            $service->users_messages->get('me', $message->getId()),
            $this->client
        );
    }

    /**
     * Hydrate the draft. Typically it will set the previously parameters on the editable draft
     * Attachments are omitted for simplicity. Maybe will add on some future version.
     *
     * @return static
     */
    public function hydrateDraft()
    {
        if (empty($this->draft->message)) {
            return $this;
        }
        $message = $this->draft->message;

        $this->setMyName($message->from->name)
            ->to(...$message->to->values()->all())
            ->cc(...$message->cc->values()->all())
            ->bcc(...$message->getBcc()->values()->all())
            ->subject($message->subject)
            ->body($message->body);

        if ($message->replyTo) {
            $this->setHeader('In-Reply-To', $message->replyTo);
        }
        if ($message->references) {
            $this->setHeader('References', $message->references);
        }
        if ($message->threadId) {
            $this->threadId = $message->threadId;
        }

        return $this;
    }
}
