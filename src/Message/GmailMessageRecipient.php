<?php
namespace Skn036\Gmail\Message;

use Illuminate\Support\Facades\Validator;

class GmailMessageRecipient
{
    /**
     * Email of the recipient
     * @var string
     */
    public $email;

    /**
     * Name of the recipient
     * @var string
     */
    public $name = '';

    /**
     * GmailMessageRecipient constructor.
     *
     * @param string $email
     * @param string|null $name
     */
    public function __construct(string $email, string|null $name = null)
    {
        $this->email = $email;
        if ($name) {
            $this->name = $name;
        }
    }

    /**
     * Set email of the recipient
     *
     * @param string $email
     * @return static
     */
    public function setEmail(string $email)
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
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Check if email is valid
     *
     * @return bool
     */
    public function isEmailValid()
    {
        $input = ['email' => $this->email];
        $validator = Validator::make($input, [
            'email' => 'required|email',
        ]);
        return !$validator->fails();
    }
}
