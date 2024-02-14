<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\ManagementController;

class ResultDaySendMailCronJon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resultdaysendmail:cron {type} {--queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cron job for send email on result day';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $management = new ManagementController();
        $type = $this->argument('type');
        $response = $management->ReslutDayCronJob($type);
        
        if ($response->getStatusCode() == 200) { // here you are checking your http status code
            return true;
        } else {
            return false;
        }
    }
}
