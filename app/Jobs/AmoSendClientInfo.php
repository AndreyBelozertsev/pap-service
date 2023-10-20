<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Domain\Client\Models\Client;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Services\AmoCRM\WebHooks\NewTelegramUser;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class AmoSendClientInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Client $client)
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $amocrmNewTelegramUserWebHook = new NewTelegramUser($this->client);
        $amocrmNewTelegramUserWebHook->index();
    }
}
