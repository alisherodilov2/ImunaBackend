<?php

namespace App\Console\Commands;

use App\Models\ClientResult;
use App\Models\ClientValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClientResultCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:result';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $clientValues = ClientValue::distinct()->get(['department_id', 'client_id'])->toArray();
        ;
        Log::info($clientValues);
        
        foreach ($clientValues as $clientValue) {
            ClientResult::updateOrCreate(
                [
                    'client_id' => $clientValue['client_id'],
                    'department_id' => $clientValue['department_id']
                ],
                [
                    'client_id' => $clientValue['client_id'],
                    'department_id' => $clientValue['department_id']
                ]
            );
        }
        return json_encode($clientValues);
    }
}
