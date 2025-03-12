<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\ClientResult;
use App\Models\ClientValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ClientDepartmentCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:result-count';

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
        $clientAll = Client::whereNotNull('parent_id')->get();
        foreach ($clientAll as $key => $item) {
            # code...
            $client = Client::find($item->id);
            $clientValue = ClientValue::where(['client_id' => $item->id, 'is_active' => 1])
           ->pluck('department_id')
                    ->unique()->count();

            $clientResult = ClientResult::where(['client_id' => $item->id,'is_check_doctor' => 'finish'])
            ->pluck('department_id')
            ->unique()->count();
            $client->update([
                'finish_department_count' => $clientResult,
                'department_count' => $clientValue,
            ]);
        }
        return 0;
    }
}
