<?php

namespace App\Console\Commands;

use Database\Seeders\ClientSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FaceDataClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:fake {count=0} {--is_pay=0}';

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
        $count = $this->argument('count');

        // Opsiyalarni olish
        $pay = $this->option('is_pay') ?? 0;
        $seeder = new ClientSeeder($count, $pay);
        $seeder->run();
        echo 'Client qo`shildi';
        return 0;
    }
}
