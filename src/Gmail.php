<?php

namespace Skn036\Gmail;

use Skn036\Google\GoogleClient;
use Skn036\Gmail\Message\FetchMessage;
use Skn036\Gmail\Exceptions\TokenNotValidException;

class Gmail extends GoogleClient
{
    /**
     * Create a new GoogleClient instance.
     * for more info on google client please check https://github.com/skn-036/laravel-google-client
     *
     * @param string|int|null  $userId id of the user. (Generally refers to the \App\Models\User model)
     * @param string|null $usingAccount email of the account to be used, when multiple accounts per user.
     * @param array<string, mixed>|null $config pass this parameter equivalent to config('google') to override the env file configuration.
     *
     * @return void
     */
    public function __construct($userId = null, $usingAccount = null, $config = null)
    {
        parent::__construct($userId, $usingAccount, $config);
    }

    /**
     * Initiate the Gmail service
     * Use it to talk with gmail api
     *
     * @return \Google_Service_Gmail
     */
    public function initiateService()
    {
        return new \Google_Service_Gmail($this);
    }

    /**
     * Get the current instance
     * sometime you may need to get the current instance of the Gmail class accessing facade
     * like $gmail = Gmail::instance();
     *
     * @return static
     */
    public function instance()
    {
        return $this;
    }

    /**
     * To fetching gmail messages
     *
     * @return FetchMessage
     * @throws TokenNotValidException
     */
    public function messages()
    {
        $this->throwExceptionIfNotAuthenticated();

        return new FetchMessage($this);
    }

    /**
     * Throws exception if the user is not authenticated on gmail
     * Services like interacting with messages, labels, drafts, etc. require the user to be authenticated.
     * Accessing these resources without being authenticated will throw an exception immediately.
     *
     * @return void
     * @throws TokenNotValidException
     */
    private function throwExceptionIfNotAuthenticated()
    {
        if (!$this->isAuthenticated()) {
            throw new TokenNotValidException();
        }
    }
}
