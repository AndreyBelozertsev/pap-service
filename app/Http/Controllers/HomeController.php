<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\AmoSendClientInfo;
use Domain\Client\Models\Client;
use Services\AmoCRM\Facades\AmoCrm;
use AmoCRM\Exceptions\AmoCRMApiException;
use Services\AmoCRM\WebHooks\NewTelegramUser;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        return view('welcome');
    }

    public function getPipelines(Request $request)
    {
        $client = AmoCrm::getAuthClient();

        $pipelinesService = $client->pipelines();
        try {
            $pipelinesCollection = $pipelinesService->get();
        } catch (AmoCRMApiException $e) {
            printError($e);
            die;
        }
        dd($pipelinesCollection);


    }
}