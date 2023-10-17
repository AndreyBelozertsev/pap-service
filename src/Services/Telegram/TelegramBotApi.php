<?php

namespace Services\Telegram;

use Throwable;
use Illuminate\Support\Facades\Http;
use Services\Telegram\Exceptions\TelegramBotApiException;




class TelegramBotApi 
{

    public const HOST = 'https://api.telegram.org/bot';

    public static function sendMessage($text,$chat_id, $token)
    {
        try{
            $response = Http::get(self::HOST . $token . '/sendMessage',[
                'chat_id' => $chat_id,
                'text' => $text
            ])->throw()->json();

            return $response['ok'] ?? false;
        }catch(Throwable $e){
            
            report(new TelegramBotApiException($e->getMessage()));
            return false;
        }
    }

}