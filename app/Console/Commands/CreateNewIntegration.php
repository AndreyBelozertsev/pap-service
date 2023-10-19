<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use AmoCRM\Client\AmoCRMApiClient;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Token\AccessToken;

class CreateNewIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amo:create-new-integration';

    /**
     * The console command description.
     *
     * @var string
     */
    public $description = 'Create a first token Amocrm Integration';

    public function handle(): int
    {
        $this->info("Создание первого токена после интеграции");


        $code = $this->ask('Введите секретный код');
        if (empty($code)) {
            $this->error('Вы не ввели секретный код');

            return self::FAILURE;
        }


        $amocrm = new AmoCRMApiClient(
            config('amocrm.client_id'),
            config('amocrm.client_secret'),
            config('amocrm.redirect_uri')
        );

        try {
            $accessToken = $amocrm->setAccountBaseDomain(config('amocrm.base_domain'))
                    ->getOAuthClient()
                    ->getAccessTokenByCode($code);
        } catch (Exception $e) {
            Log::debug($e->getMessage());
            $this->error('Внесенные даты не прошли проверку на стороне AmoCrm, проверьте данные! ' . $e->getMessage());
            return self::FAILURE;
        } 

        $tokenModel = config('amocrm.models.teoken');

        $tokenModel = config('amocrm.models.token');
        $tokenModel::create([
            'access_token'=> $accessToken->getToken(),
            'expires_in'=> $accessToken->getExpires(),
            'refresh_token' => $accessToken->getRefreshToken(),
            'base_domain' => config('amocrm.base_domain')
        ]);


        $this->info("Интеграция осуществлена успешно");

        return self::SUCCESS;
    }
}
