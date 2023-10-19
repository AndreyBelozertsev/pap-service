<?php


namespace Services\AmoCRM;

use AmoCRM\Client\AmoCRMApiClient;
use Services\AmoCRM\Models\AmoCrmToken;
use Illuminate\Contracts\Config\Repository;
use League\OAuth2\Client\Token\AccessToken;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use League\OAuth2\Client\Token\AccessTokenInterface;

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
     * The AmoCRM token instance.
     *
     * @var 
     */
    protected $token;
    /**
     * Create a new manager instance.
     *
     */
    public function __construct($config)
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
                $this->config['client_id'],
                $this->config['client_secret'],
                $this->config['redirect_uri']
            );
        }

        return $this->client;
    }

    public function getToken(string $client_id = null)
    {
        $tokenModel = config('amocrm.models.token');
        $token = $tokenModel::latest()->first();
        if($token && !$this->token instanceof AccessToken){
            $this->token = new AccessToken(
                [
                    'access_token' =>$token->access_token,
                    'expires_in' =>$token->expires_in - time(),
                    'refresh_token' => $token->refresh_token,
                    'baseDomain' => $token->base_domain
                ]
            );
        }

        return $this->token;
    }

    public function getAuthClient()
    {

        $accessToken = $this->getToken();

        if (empty($accessToken)) {
            throw new AmoCRMMissedTokenException('Access token not found');
        }

        $this->getClient()
            ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
            ->setAccessToken($accessToken)
            ->onAccessTokenRefresh(
                function (AccessTokenInterface $accessToken, string $baseDomain) {
                    $tokenModel = config('amocrm.models.token');
                    $tokenModel::create([
                        'access_token'=> $accessToken->getToken(),
                        'expires_in'=> $accessToken->getExpires(),
                        'refresh_token' => $accessToken->getRefreshToken(),
                        'base_domain' => $baseDomain
                    ]);
            });
    
        return $this->client;

    }

}