<?php

namespace App\Traits;

use App\Models\History;
use App\Models\InAndOutPayment;
use App\Models\PayOffice;

trait HistoryTraid
{

    public function history()
    {
        $in_pay = InAndOutPayment::where('status', 'in_payment')->where('parent_id', '!=', null)->get();
        $out_pay = InAndOutPayment::where('status', 'out_payment')->where('parent_id', '!=', null)->get();
        $convert_sum = $in_pay->whereIn('payment_type', ['cash_$', 'nation_$'])->sum('convert_price');
        $convert_dol = $in_pay->whereIn('payment_type', ['cash_sum', 'nation_sum'])->sum('convert_price');
        $payoffice_dol = PayOffice::where('type', '$')->sum('price');
        $payoffice_sum = PayOffice::where('type', 'sum')->sum('price');
        $history = History::whereDate('created_at', now())->first();
        $all_dol = ($payoffice_dol +
            $out_pay
                ->whereIn('payment_type', ['cash_$', 'nation_$'])->map(function ($item) {
                return ($item->payment_type == 'cash_$' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total) - $item->exchange_price;
            })->sum())
         -
        $in_pay->whereIn('payment_type', ['cash_$', 'nation_$'])->map(function ($item) {
            return ($item->payment_type == 'cash_$' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total) - $item->exchange_price;
        })->sum() - $convert_dol;
        $all_sum = ($payoffice_sum +
            $out_pay
                ->whereIn('payment_type', ['cash_sum', 'nation_sum'])->map(function ($item) {
                return ($item->payment_type == 'cash_sum' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total) - $item->exchange_price;
            })->sum())
         -
        $in_pay->whereIn('payment_type', ['cash_sum', 'nation_sum'])->map(function ($item) {
            return ($item->payment_type == 'cash_sum' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total) - $item->exchange_price;
        })->sum() - $convert_sum;
        // if ($history) {
        //     $history->update([
        //         'user_id' => 1,
        //         'price_$' => $all_dol,
        //         'price_sum' => $all_sum,
        //     ]);
        // } else {
        //     $history = History::create([
        //         'user_id' => 1,
        //         'price_$' => $all_dol,
        //         'price_sum' => $all_sum,
        //     ]);
        // }
        return [
            'id' => 1,
            'price_$' => $all_dol,
            'price_sum' => $all_sum,
        ];
    }
}
