<?php 

namespace Services\Telegraph;

use DefStudio\Telegraph\Telegraph;
use Services\Telegraph\Concerns\ResponseChatJoin;

class TelegraphCustom extends Telegraph
{
    use ResponseChatJoin;
    
    public const ENDPOINT_APPROVE_CHAT_JOIN_REQUEST = 'approveChatJoinRequest';


}