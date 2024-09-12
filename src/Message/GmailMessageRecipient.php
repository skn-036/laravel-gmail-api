<?php
namespace Skn036\Gmail\Message;

class GmailMessageRecipient
{
    /**
     * Email of the recipient
     * @var string|null
     */
    public $email;

    /**
     * Name of the recipient
     * @var string|null
     */
    public $name;

    /**
     * GmailMessageRecipient constructor.
     *
     * @param string|null $email
     * @param string|null $name
     */
    public function __construct($email = null, $name = null)
    {
        $this->email = $email;
        $this->name = $name;
    }

    /**
     * Set email of the recipient
     *
     * @param string $email
     * @return static
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Set name of the recipient
     *
     * @param string $name
     * @return static
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
