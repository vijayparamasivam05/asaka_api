<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\CompanyController;

class CronjobTypeTwo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjobtypetwo:cron {type} {--queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cron job type 2  and type 4 for updating the companies status';

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
        $company = new CompanyController();
        $type = $this->argument('type');
        $response = $company->getCompanyByStartDate($type);
        
        if ($response->getStatusCode() == 200) { // here you are checking your http status code
            return true;
        } else {
            return false;
        }
    }
}
