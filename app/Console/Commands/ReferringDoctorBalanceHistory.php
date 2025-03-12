<?php

namespace App\Console\Commands;

use App\Models\DirectorSetting;
use App\Models\ReferringDoctorBalance;
use App\Models\ReferringDoctorServiceContribution;
use Illuminate\Console\Command;

class ReferringDoctorBalanceHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:contribution-history';

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
        $ReferringDoctorBalance = ReferringDoctorBalance::with('clientValue.service')->get();
        foreach ($ReferringDoctorBalance as $key => $value) {
            $contribution_history = [];
            $total_kounteragent_contribution_price = 0;
            $total_kounteragent_doctor_contribution_price = 0;
            $total_doctor_contribution_price = 0;
            foreach ($value->clientValue as $item) {
                $doctorContributionPrice = $item->service->kounteragent_contribution_price ?? 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                $qtyDc = $item->qty;
                $totalPrice_k_c = $item->total_price;
                $setting = DirectorSetting::where('user_id', $item->service->user_id)->first();
                if ($setting->is_contribution_kounteragent) {
                    $totalPrice_k_c = ($item->discount <= 100)
                        ? $item->total_price  -  ($item->total_price / 100) * $item->discount
                        : $item->total_price - ($item->discount);
                }

                // Hisoblash logikasi
                $resTotal_d_c =  ($doctorContributionPrice <= 100)
                    ? ($totalPrice_k_c * $doctorContributionPrice / 100)
                    : ($doctorContributionPrice * $qtyDc);
                $total_kounteragent_contribution_price = $total_kounteragent_contribution_price + $resTotal_d_c;


                $kounterdoctorContributionPrice = 0; // Agar qiymat mavjud bo'lmasa 0 qilamiz
                if ($item->service->kounteragent_doctor_contribution_price > 0) {
                    $kounterdoctorContributionPrice =  $item->service->kounteragent_doctor_contribution_price ?? 0;
                } else {
                    $kounterdoctorContributionPrice = ReferringDoctorServiceContribution::where([
                        'ref_doc_id' => $value->referring_doctor_id,
                        'service_id' => $item->service->id
                    ])->first()->contribution_price ?? 0;
                }
                // Log::info('   $kounterdoctorContributionPrice', [$kounterdoctorContributionPrice]);
                $KountertotalPrice = $item->total_price;

                if ($setting->is_contribution_kt_doc) {
                    $KountertotalPrice = ($item->discount <= 100)
                        ? $KountertotalPrice  -  ($KountertotalPrice / 100) * $item->discount
                        : $KountertotalPrice - ($item->discount);
                }

                $KounterResTotal = ($kounterdoctorContributionPrice <= 100)
                    ? ($KountertotalPrice * $kounterdoctorContributionPrice / 100)
                    : ($kounterdoctorContributionPrice * $qtyDc);
                $total_kounteragent_doctor_contribution_price = $total_kounteragent_doctor_contribution_price +    $KounterResTotal;
                $contribution_history[] = [
                    'service_id' => $item->service->id,
                    'department_id' => $item->service->department_id,
                    'price' => $item->price,
                    'qty' => $item->qty,
                    'client_value' => $item->id,
                    'kounteragent_contribution_price' => $doctorContributionPrice,
                    'kounteragent_doctor_contribution_price' => $KountertotalPrice,
                    'total_kounteragent_contribution_price' => $resTotal_d_c,
                    'total_kounteragent_doctor_contribution_price' => $KounterResTotal,
                ];
                ReferringDoctorBalance::find($value->id)->update(
                    [
                        'contribution_history' => json_encode($contribution_history)
                    ]
                );
            }
        }
        var_dump($ReferringDoctorBalance->count());
        return;
    }
}
