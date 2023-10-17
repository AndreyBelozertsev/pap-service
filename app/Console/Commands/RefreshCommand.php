<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RefreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh project';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if(app()->isProduction()){
            return Command::FAILURE;
        }
        $this->call('cache:clear');
        Storage::deleteDirectory('public/files/');
        Storage::deleteDirectory('public/images/');
        $this->call('migrate:fresh',[
            '--seed' => true
        ]);
        
        return Command::SUCCESS;
    }
}
