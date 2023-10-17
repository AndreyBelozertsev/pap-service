<?php

/** @noinspection PhpDocSignatureIsNotCompleteInspection */

namespace Services\Telegraph\DTO;


use DefStudio\Telegraph\DTO\User;
use Illuminate\Contracts\Support\Arrayable;

class InviteLink implements Arrayable
{

    private string $inviteLink;
    private User $creator;
    private int $pendingJoinRequestCount;
    private bool $createsJoinRequest;
    private bool $isPrimary;
    private bool $isRevoked;

    private function __construct()
    {
    }

    /**
     * @param array{ inviteLink:string, creator:User, pendingJoinRequestCount:int, createsJoinRequest:bool, isPrimary:bool, isRevoked:bool } $data
     */
    public static function fromArray(array $data): InviteLink
    {
        $inviteLink = new self();

        $inviteLink->inviteLink = $data['invite_link'];
        $inviteLink->creator = User::fromArray($data['creator']);
        $inviteLink->pendingJoinRequestCount = $data['pending_join_request_count'];
        $inviteLink->createsJoinRequest = $data['creates_join_request'];
        $inviteLink->isPrimary = $data['is_primary'];
        $inviteLink->isRevoked = $data['is_revoked'];

        return $inviteLink;
    }


    public function inviteLink(): string
    {
        return $this->inviteLink;
    }

    public function creator(): User
    {
        return $this->creator;
    }

    public function pendingJoinRequestCount(): int
    {
        return $this->pendingJoinRequestCount;
    }

    public function createsJoinRequest(): bool
    {
        return $this->createsJoinRequest;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function isRevoked(): bool
    {
        return $this->isRevoked;
    }

    public function toArray(): array
    {
        return array_filter([
            'invite_link' => $this->inviteLink,
            'creator' => $this->creator->toArray(),
            'pending_join_request_count' => $this->pendingJoinRequestCount,
            'creates_join_request' => $this->createsJoinRequest,
            'is_primary' => $this->isPrimary,
            'is_revoked' => $this->isRevoked,
        ]);
    }
}