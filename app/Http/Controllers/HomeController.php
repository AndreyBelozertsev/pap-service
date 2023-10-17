<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Services\AmoCRM\AmoCrmManager;
use Services\AmoCRM\Facades\AmoCrm;



class HomeController extends Controller
{
    public function index(Request $request, AmoCrmManager $amocrm)
    {
 
        dd($amocrm->getClient());
        dd(AmoCrm::getClient());
        
        $amocrm->getClient();
        return view('welcome');
    }

}
