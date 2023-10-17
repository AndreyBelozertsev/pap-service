<?php

namespace App\Logging\Telegram;

use Monolog\Logger;

use Services\Telegram\TelegramBotApi;
use Monolog\Handler\AbstractProcessingHandler;

class TelegramLoggerHandler extends AbstractProcessingHandler
{

    protected $chat_id;
    protected $token;

    public function __construct(array $config)
    {
        $level = Logger::toMonologLevel($config['level']);
        $this->chat_id = $config['chat_id'];
        $this->token = $config['token'];
        parent::__construct($level);
    }

    protected function write($record) :void
    {
        TelegramBotApi::sendMessage( $record['formatted'] , $this->chat_id, $this->token ); 
    }
    
}