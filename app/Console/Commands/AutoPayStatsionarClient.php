<?php

namespace App\Console\Commands;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoPayStatsionarClient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:auto-pay-statsionar-client';

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
    function addDaysToDate($date, $days)
    {
        return Carbon::parse($date)->addDays($days)->format('Y-m-d');
    }
    function daysBetweenDates($date1, $date2)
    {
        $start = Carbon::parse($date1);
        $end = Carbon::parse($date2);
        return $start->diffInDays($end);
    }
    public function handle()
    {

        $clints = Client::whereNotNull('parent_id')
            ->where('is_statsionar', 1)
            ->where('is_finish_statsionar', '!=', 1)
            ->whereHas('parent', function ($q) {
                $q->where('balance', '>', 0);
            })
            ->with('parent')
            ->get();
        $data = [];
        foreach ($clints as $result) {
            $balance = $result->parent->balance;

            if ($result->day_qty > 0) {
                $romQty = $this->daysBetweenDates($result->addmission_date, now()->addDay(3)->format('Y-m-d'));
                $data[] =   $romQty;
                if ($romQty <= $result->day_qty) {
                    $price = $result->statsionar_room_price;
                    $totalPrice = $price * $romQty;
                    if ($totalPrice > $result->statsionar_room_price_pay) {
                        $payPrice = $totalPrice - $result->statsionar_room_price_pay;
                        if ($balance >= $payPrice) {
                            Client::find($result->id)->update([
                                'statsionar_room_price_pay' => $result->statsionar_room_price_pay + $payPrice
                            ]);
                            Client::find($result->parent->id)->update([
                                'balance' => $balance - $payPrice
                            ]);
                        } else {
                            Client::find($result->id)->update([
                                'statsionar_room_price_pay' => $result->statsionar_room_price_pay + $balance
                            ]);
                            Client::find($result->parent->id)->update([
                                'balance' => 0
                            ]);
                        }
                    }
                }
            } else {
                $romQty = $this->daysBetweenDates($result->addmission_date, now()->format('Y-m-d'));
            }
        }
        // var_dump( $data);
        return $this->info(json_encode($data));
    }
}
