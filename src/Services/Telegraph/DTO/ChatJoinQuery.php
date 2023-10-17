<?php

/** @noinspection PhpDocSignatureIsNotCompleteInspection */

namespace Services\Telegraph\DTO;

use Illuminate\Support\Str;
use DefStudio\Telegraph\DTO\Chat;
use DefStudio\Telegraph\DTO\User;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;

class ChatJoinQuery implements Arrayable
{

    private User $from;

    private Chat $chat;

    private InviteLink $inviteLink;

    private function __construct()
    {
    }

    /**
     * @param array{from:array<string, mixed>, chat:array<string, mixed>, invitedLink:array<string, mixed>,} $data
     */
    public static function fromArray(array $data): ChatJoinQuery
    {
        $chatJoinQuery = new self();

        /* @phpstan-ignore-next-line */
        $chatJoinQuery->from = User::fromArray($data['from']);

        $chatJoinQuery->chat = Chat::fromArray($data['chat']);

        $chatJoinQuery->inviteLink = InviteLink::fromArray($data['invite_link']);

        return $chatJoinQuery;
    }


    public function from(): User
    {
        return $this->from;
    }

    public function chat(): Chat
    {
        return $this->chat;
    }


    public function inviteLink(): InviteLink
    {
        return $this->inviteLink;
    }


    public function toArray(): array
    {
        return array_filter([
            'from' => $this->from->toArray(),
            'chat' => $this->chat->toArray(),
            'invite_link' => $this->inviteLink->toArray(),
        ]);
    }
}
