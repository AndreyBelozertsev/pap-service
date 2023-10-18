<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Services\AmoCRM\AmoCrmManager;
use Services\AmoCRM\Facades\AmoCrm;



class HomeController extends Controller
{
    public function index(Request $request, AmoCrmManager $amocrm)
    {
        $accessToken = getToken();

        $amocrm->setAccessToken($accessToken)
            ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
            ->onAccessTokenRefresh(
                function (AccessTokenInterface $accessToken, string $baseDomain) {
                    saveToken(
                        [
                            'accessToken' => $accessToken->getToken(),
                            'refreshToken' => $accessToken->getRefreshToken(),
                            'expires' => $accessToken->getExpires(),
                            'baseDomain' => $baseDomain,
                        ]
                    );
                }
            );


        //Получим свойства аккаунта со всеми доступными свойствами
        try {
            $account = $amocrm->account()->getCurrent(AccountModel::getAvailableWith());
            dump($account->toArray());
        } catch (AmoCRMApiException $e) {
            printError($e);
        }
        dd($amocrm->getClient());
        dd(AmoCrm::getClient());
        
        $amocrm->getClient();
        return view('welcome');
    }

}
