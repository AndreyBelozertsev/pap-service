<?php 

/** @noinspection PhpUnhandledExceptionInspection */
namespace Services\Telegraph\Concerns;

use Services\Telegraph\TelegraphCustom;
use DefStudio\Telegraph\Exceptions\BotCommandException;


/**
 * @mixin Telegraph
 */

trait ResponseChatJoin
{
    public function approveChatJoin(string $chat_id, int $user_id): TelegraphCustom
    {
        $telegraph = clone $this;

        $telegraph->endpoint = self::ENDPOINT_APPROVE_CHAT_JOIN_REQUEST;

        $telegraph->data['chat_id'] = $chat_id;
        $telegraph->data['user_id'] = $user_id;

        return $telegraph;
    }
}
