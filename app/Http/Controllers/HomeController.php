<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\AmoSendClientInfo;
use Domain\Client\Models\Client;
use Services\AmoCRM\WebHooks\NewTelegramUser;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        return view('welcome');
    }
}