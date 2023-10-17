<?php


namespace Services\AmoCRM;

use AmoCRM\Client\AmoCRMApiClient;
use Illuminate\Contracts\Config\Repository;


class AmoCrmManager
{
    /**
     * The config instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The AmoCRM client instance.
     *
     * @var AmoCRM\Client\AmoCRMApiClient
     */
    protected $client;

    /**
     * Create a new manager instance.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Get the config instance.
     *
     * @return \Illuminate\Contracts\Config\Repository
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get the AmoCRM client instance.
     *
     * @return AmoCRM\Client\AmoCRMApiClient;
     */
    public function getClient()
    {
        if (!$this->client instanceof AmoCRMApiClient) {
            $this->client = new AmoCRMApiClient(
                $this->config->get('amocrm.clientId'),
                $this->config->get('amocrm.clientSecret'),
                $this->config->get('amocrm.redirectUri')
            );
        }
        return $this->client;
    }

}