<?php
namespace Skn036\Gmail\Label;

class GmailLabel
{
    /**
     * Thread from gmail
     * @var \Google_Service_Gmail_Label|\Google\Service\Gmail\Label
     */
    protected $label;

    /**
     * Label Id
     * @var string
     */
    public $id;

    /**
     * Label name
     * @var string
     */
    public $name;

    /**
     * Label type. enum: 'system' | 'user'
     * @var string
     */
    public $type;

    /**
     * Label message list visibility. enum: 'labelShow' | 'labelShowIfUnread' | 'labelHide'
     * @var string|null
     */
    public $labelListVisibility = null;

    /**
     * Label message list visibility. enum: 'show' | 'hide'
     * @var string|null
     */
    public $messageListVisibility = null;

    /**
     * Total messages in the label
     * @var int
     */
    public $messagesTotal;

    /**
     * Unread messages in the label
     * @var int
     */
    public $messagesUnread;

    /**
     * Total threads in the label
     * @var int
     */
    public $threadsTotal;

    /**
     * Unread threads in the label
     * @var int
     */
    public $threadsUnread;

    /**
     * Label color
     * @var string|null
     */
    public $textColor = null;

    /**
     * Label background color
     * @var string|null
     */
    public $backgroundColor = null;

    /**
     * Summary of __construct
     * @param \Google_Service_Gmail_Label|\Google\Service\Gmail\Label $label
     *
     * @throws \Exception
     */
    public function __construct($label)
    {
        if ($label instanceof \Exception) {
            throw $label;
        }
        if (!($label instanceof \Google_Service_Gmail_Label)) {
            throw new \Exception('label is not instance of \Google_Service_Gmail_Label');
        }

        $this->label = $label;

        $this->prepareLabel();
    }

    /**
     * Get raw label from gmail
     *
     * @return \Google_Service_Gmail_Label|\Google\Service\Gmail\Label
     */
    public function getRawLabel()
    {
        return $this->label;
    }

    /**
     * Set label id
     *
     * @return static
     */
    protected function setId()
    {
        $this->id = $this->label->getId();
        return $this;
    }

    /**
     * Set label name
     *
     * @return static
     */
    protected function setName()
    {
        $this->name = $this->label->getName();
        return $this;
    }

    /**
     * Set label type
     *
     * @return static
     */
    protected function setType()
    {
        $this->type = $this->label->getType();
        return $this;
    }

    /**
     * Set label list visibility
     *
     * @return static
     */
    protected function setLabelListVisibility()
    {
        $this->labelListVisibility = $this->label->getLabelListVisibility();
        return $this;
    }

    /**
     * Set message list visibility
     *
     * @return static
     */
    protected function setMessageListVisibility()
    {
        $this->messageListVisibility = $this->label->getMessageListVisibility();
        return $this;
    }

    /**
     * Set total messages in the label
     *
     * @return static
     */
    protected function setMessagesTotal()
    {
        $this->messagesTotal = $this->label->getMessagesTotal();
        return $this;
    }

    /**
     * Set unread messages in the label
     *
     * @return static
     */
    protected function setMessagesUnread()
    {
        $this->messagesUnread = $this->label->getMessagesUnread();
        return $this;
    }

    /**
     * Set total threads in the label
     *
     * @return static
     */
    protected function setThreadsTotal()
    {
        $this->threadsTotal = $this->label->getThreadsTotal();
        return $this;
    }

    /**
     * Set unread threads in the label
     *
     * @return static
     */
    protected function setThreadsUnread()
    {
        $this->threadsUnread = $this->label->getThreadsUnread();
        return $this;
    }

    /**
     * Set label text and background colors
     *
     * @return static
     */
    protected function setColors()
    {
        $color = $this->label->getColor();
        if ($color && $color instanceof \Google_Service_Gmail_LabelColor) {
            $this->textColor = $color->getTextColor();
            $this->backgroundColor = $color->getBackgroundColor();
        }

        return $this;
    }

    /**
     * Prepare the label from \Google_Service_Gmail_Label instance
     *
     * @return static
     */
    protected function prepareLabel()
    {
        return $this->setId()
            ->setName()
            ->setType()
            ->setLabelListVisibility()
            ->setMessageListVisibility()
            ->setMessagesTotal()
            ->setMessagesUnread()
            ->setThreadsTotal()
            ->setThreadsUnread()
            ->setColors();
    }
}
