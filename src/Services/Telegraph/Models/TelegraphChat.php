<?php
namespace Services\Telegraph\Models;

use Domain\Client\Models\Client;
use DefStudio\Telegraph\Models\TelegraphChat as TelegraphChatOriginal;

class TelegraphChat extends TelegraphChatOriginal
{
    public function client()
    {
        return $this->hasOne(Client::class);
    }

}