<?php
namespace Skn036\Gmail\Draft;

use Skn036\Gmail\Draft\Sendable\Draft;
use Skn036\Gmail\Gmail;
use Skn036\Gmail\Facades\Gmail as GmailFacade;
use Skn036\Gmail\Message\GmailMessage;

class GmailDraft
{
    /**
     * Draft from gmail
     * @var \Google_Service_Gmail_Draft
     */
    protected $draft;

    /**
     * Gmail Client
     * @var Gmail|GmailFacade
     */
    private $client;

    /**
     * Draft id
     * @var string
     */
    public $id;

    /**
     * Message of the draft
     * @var GmailMessage
     */
    public $message;

    /**
     * Summary of __construct
     * @param \Google_Service_Gmail_Draft $draft
     * @param Gmail|GmailFacade $client
     *
     * @throws \Exception
     */
    public function __construct($draft, Gmail|GmailFacade $client)
    {
        if ($draft instanceof \Exception) {
            throw $draft;
        }
        if (!($draft instanceof \Google_Service_Gmail_Draft)) {
            throw new \Exception('message is not instance of \Google_Service_Gmail_Draft');
        }

        $this->client = $client;
        $this->draft = $draft;

        $this->prepareDraft();
    }

    /**
     * Creates a new updatable draft instance.
     * Once updating is done, you should call `update` method to save changes
     * Or you can call `send` method to send directly
     *
     * @return Draft
     */
    public function edit()
    {
        return (new Draft($this->client, $this))->hydrateDraft();
    }

    /**
     * Sending the draft directly without editing
     *
     * @param array $optParams
     * @return GmailMessage
     */
    public function send(array $optParams = [])
    {
        $service = $this->client->initiateService();
        $message = $service->users_drafts->send('me', $this->draft, $optParams);

        return new GmailMessage(
            $service->users_messages->get('me', $message->getId()),
            $this->client
        );
    }

    /**
     * Deletes the draft
     *
     * @param array $optParams
     *
     * @return void
     */
    public function delete(array $optParams = [])
    {
        $service = $this->client->initiateService();
        $service->users_drafts->delete('me', $this->id, $optParams);
    }

    /**
     * Returns the raw draft
     * @return \Google_Service_Gmail_Draft|\Google\Service\Gmail\Draft
     */
    public function getRawDraft()
    {
        return $this->draft;
    }

    /**
     * Sets the id of the draft
     * @return static
     */
    protected function setId()
    {
        $this->id = $this->draft->getId();
        return $this;
    }

    /**
     * Sets the underlying message of the draft
     * @return static
     */
    protected function setMessage()
    {
        $this->message = new GmailMessage($this->draft->getMessage(), $this->client);
        return $this;
    }

    /**
     * Prepare the draft from \Google_Service_Gmail_Draft instance
     * @return static
     */
    protected function prepareDraft()
    {
        return $this->setId()->setMessage();
    }
}
