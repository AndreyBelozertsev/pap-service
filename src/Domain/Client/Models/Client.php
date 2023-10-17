<?php

namespace Domain\Client\Models;

use Illuminate\Database\Eloquent\Model;
use Services\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'mail',
        'phone',
        'telegraph_chat_id',
    ];

    public function chat()
    {
        return $this->belongsTo(TelegraphChat::class);
    }
}
