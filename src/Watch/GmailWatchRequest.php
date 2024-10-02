<?php
namespace Skn036\Gmail\Watch;

use Skn036\Gmail\Gmail;
use Skn036\Gmail\Facades\Gmail as GmailFacade;
use Skn036\Gmail\Exceptions\TokenNotValidException;

class GmailWatchRequest
{
    /**
     * Gmail Client
     * @var Gmail|GmailFacade
     */
    protected $client;

    /**
     * Gmail service
     * @var \Google_Service_Gmail
     */
    protected $service;

    /**
     * Create a new GmailLabel instance.
     *
     * @param Gmail|GmailFacade $client
     *
     * @throws TokenNotValidException
     */
    public function __construct(Gmail|GmailFacade $client)
    {
        if (!$client->isAuthenticated()) {
            $client->throwExceptionIfNotAuthenticated();
        }

        $this->client = $client;
        $this->service = $client->initiateService();
    }

    /**
     * Start watch
     *
     * @param array $labelIds
     * @param string $labelFilterBehaviour enum: 'include' | 'exclude'
     * @param array $optParams
     *
     * @return \Google_Service_Gmail_WatchResponse
     * @throws \Google\Service\Exception
     */
    public function start(
        array $labelIds = [],
        string|null $labelFilterBehaviour = null,
        array $optParams = []
    ) {
        $topic = config('google.pub_sub_topic');

        $watchRequest = new \Google_Service_Gmail_WatchRequest();
        $watchRequest->setTopicName($topic);

        if (count($labelIds)) {
            $watchRequest->setLabelIds($labelIds);
            if ($labelFilterBehaviour) {
                $watchRequest->setLabelFilterBehavior($labelFilterBehaviour);
            }
        }

        $watchResponse = $this->service->users->watch('me', $watchRequest, $optParams);
        return $watchResponse;
    }

    /**
     * Stop watch
     *
     * @param array $optParams
     *
     * @return void
     */
    public function stop(array $optParams = [])
    {
        $this->service->users->stop('me', $optParams);
    }
}
