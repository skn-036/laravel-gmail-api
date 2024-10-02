<?php
namespace Skn036\Gmail\Label;

use Skn036\Gmail\Gmail;
use Skn036\Gmail\Facades\Gmail as GmailFacade;
use Skn036\Gmail\Exceptions\TokenNotValidException;

class GmailLabelResponse
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
     * List labels
     *
     * @param array $optParams
     *
     * @return GmailLabelsList
     * @throws \Google\Service\Exception
     */
    public function list(array $optParams = [])
    {
        $labelResponse = $this->getGmailLabelListResponse($optParams);

        $labels = $labelResponse->getLabels();
        $processedLabels = array_map(
            fn($label) => new GmailLabel($label),
            array_values($this->getLabelDetailsOnBatch($labels))
        );
        return new GmailLabelsList($processedLabels);
    }

    /**
     * Get label
     *
     * @param string $labelId
     * @param array $optParams
     *
     * @return GmailLabel
     * @throws \Google\Service\Exception
     */
    public function get(string $labelId, array $optParams = [])
    {
        $label = $this->service->users_labels->get('me', $labelId, $optParams);
        return new GmailLabel($label);
    }

    /**
     * Creates a new label
     * following params are valid
     *
     * name: string (required)
     * messageListVisibility: enum ('labelShow' | 'labelShowIfUnread' | 'labelHide')
     * labelListVisibility: enum ('show' | 'hide')
     * textColor: string
     * backgroundColor: string
     *
     * @param array $params
     * @param array $optParams
     *
     * @return GmailLabel
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.labels
     */
    public function create(array $params, array $optParams = [])
    {
        $label = $this->paramsToLabelPayload($params);
        $createdLabel = $this->service->users_labels->create('me', $label, $optParams);
        return $this->get($createdLabel->getId());
    }

    /**
     * updates the given label
     * following params are valid
     *
     * name: string
     * messageListVisibility: enum ('labelShow' | 'labelShowIfUnread' | 'labelHide')
     * labelListVisibility: enum ('show' | 'hide')
     * textColor: string
     * backgroundColor: string
     *
     * @param string $labelId
     * @param array $params
     * @param array $optParams
     *
     * @return GmailLabel
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.labels
     */
    public function update(string $labelId, array $params, array $optParams = [])
    {
        $label = $this->paramsToLabelPayload($params);
        $updatedLabel = $this->service->users_labels->update('me', $labelId, $label, $optParams);
        return $this->get($updatedLabel->getId());
    }

    /**
     * Deletes the given label
     *
     * @param string $labelId
     * @param array $optParams
     *
     * @return void
     *
     * @see https://developers.google.com/gmail/api/reference/rest/v1/users.labels
     */
    public function delete(string $labelId, array $optParams = [])
    {
        $this->service->users_labels->delete('me', $labelId, $optParams);
    }

    /**
     * List labels request to gmail
     *
     * @param array $optParams
     *
     * @return \Google_Service_Gmail_ListLabelsResponse
     * @throws \Google\Service\Exception
     */
    protected function getGmailLabelListResponse($optParams = [])
    {
        return $this->service->users_labels->listUsersLabels('me', $optParams);
    }

    /**
     * Get label request to gmail
     *
     * @param string $id
     * @return \Google_Service_Gmail_Label
     */
    protected function getGmailLabelResponse($id)
    {
        return $this->service->users_labels->get('me', $id);
    }

    /**
     * From list labels response, get detailed response of every labels
     *
     * @param array<\Google_Service_Gmail_Label> $labels
     * @return array<\Google_Service_Gmail_Label>
     */
    protected function getLabelDetailsOnBatch($labels)
    {
        $this->client->setUseBatch(true);
        $batch = $this->service->createBatch();
        foreach ($labels as $label) {
            // @phpstan-ignore-next-line
            $batch->add($this->getGmailLabelResponse($label->getId()));
        }

        return $batch->execute();
    }

    /**
     * Convert params to label payload
     *
     * @param array $params
     *
     * @return \Google_Service_Gmail_Label|\Google\Service\Gmail\Label
     */
    private function paramsToLabelPayload(array $params)
    {
        $label = new \Google\Service\Gmail\Label();
        if (array_key_exists('name', $params)) {
            $label->setName($params['name']);
        }
        if (array_key_exists('messageListVisibility', $params)) {
            $label->setMessageListVisibility($params['messageListVisibility']);
        }
        if (array_key_exists('labelListVisibility', $params)) {
            $label->setLabelListVisibility($params['labelListVisibility']);
        }
        if (
            array_key_exists('textColor', $params) ||
            array_key_exists('backgroundColor', $params)
        ) {
            $color = new \Google\Service\Gmail\LabelColor();
            if (array_key_exists('textColor', $params)) {
                $color->setTextColor($params['textColor']);
            }
            if (array_key_exists('backgroundColor', $params)) {
                $color->setBackgroundColor($params['backgroundColor']);
            }
            $label->setColor($color);
        }

        return $label;
    }
}
