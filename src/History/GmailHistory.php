<?php
namespace Skn036\Gmail\History;

class GmailHistory
{
    /**
     * Thread from gmail
     * @var \Google_Service_Gmail_History|\Google\Service\Gmail\History
     */
    protected $history;

    /**
     * History Id
     * @var string
     */
    public $id;

    /**
     * Action
     * @var string enum : 'message-added' | 'message-deleted' | 'labels-added' | 'labels-removed'
     */
    public $action;

    /**
     * Messages
     * @var array<\Google_Service_Gmail_Message|\Google\Service\Gmail\Message>
     */
    public $messages;

    /**
     * Messages added
     * @var array<\Google_Service_Gmail_HistoryMessageAdded|\Google\Service\Gmail\HistoryMessageAdded>
     */
    public $messagesAdded;

    /**
     * Messages deleted
     * @var array<\Google_Service_Gmail_HistoryMessageDeleted|\Google\Service\Gmail\HistoryMessageDeleted>
     */
    public $messagesDeleted;

    /**
     * Labels added
     * @var array<\Google_Service_Gmail_HistoryLabelAdded|\Google\Service\Gmail\HistoryLabelAdded>
     */
    public $labelsAdded;

    /**
     * Labels removed
     * @var array<\Google_Service_Gmail_HistoryLabelRemoved|\Google\Service\Gmail\HistoryLabelRemoved>
     */
    public $labelsRemoved;

    /**
     * Summary of __construct
     * @param \Google_Service_Gmail_History|\Google\Service\Gmail\History $history
     *
     * @throws \Exception
     */
    public function __construct($history)
    {
        if ($history instanceof \Exception) {
            throw $history;
        }
        if (!($history instanceof \Google_Service_Gmail_History)) {
            throw new \Exception('history is not instance of \Google_Service_Gmail_History');
        }

        $this->history = $history;

        $this->prepareHistory();
    }

    /**
     * Get raw history from gmail
     * @return \Google_Service_Gmail_History|\Google\Service\Gmail\History
     */
    public function getRawHistory()
    {
        return $this->history;
    }

    /**
     * Sets id
     * @return static
     */
    protected function setId()
    {
        $this->id = $this->history->getId();
        return $this;
    }

    /**
     * Sets messages
     * @return static
     */
    protected function setMessages()
    {
        $this->messages = $this->history->getMessages();
        return $this;
    }

    /**
     * Sets messages added
     * @return static
     */
    protected function setMessagesAdded()
    {
        $this->messagesAdded = $this->history->getMessagesAdded();
        if (count($this->messagesAdded)) {
            $this->action = 'message-added';
        }
        return $this;
    }

    /**
     * Sets messages deleted
     * @return static
     */
    protected function setMessagesDeleted()
    {
        $this->messagesDeleted = $this->history->getMessagesDeleted();
        if (count($this->messagesDeleted)) {
            $this->action = 'message-deleted';
        }
        return $this;
    }

    /**
     * Sets labels added
     * @return static
     */
    protected function setLabelsAdded()
    {
        $this->labelsAdded = $this->history->getLabelsAdded();
        if (count($this->labelsAdded)) {
            $this->action = 'labels-added';
        }
        return $this;
    }

    /**
     * Sets labels removed
     * @return static
     */
    protected function setLabelsRemoved()
    {
        $this->labelsRemoved = $this->history->getLabelsRemoved();
        if (count($this->labelsRemoved)) {
            $this->action = 'labels-removed';
        }
        return $this;
    }

    /**
     * Prepare history
     * @return static
     */
    protected function prepareHistory()
    {
        $this->setId()
            ->setMessages()
            ->setMessagesAdded()
            ->setMessagesDeleted()
            ->setLabelsAdded()
            ->setLabelsRemoved();

        return $this;
    }
}
