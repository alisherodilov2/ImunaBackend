<?php

namespace App\Http\Resources\Branch;

use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $in_pay = $this->inAndOutPayment->where('status', 'in_payment')->where('parent_id', '!=', null)
        ;
        $out_pay = $this->inAndOutPayment->where('status', 'out_payment')->where('parent_id', '!=', null);
        $gave_ = 0;
        $gave_sum = 0;
        // $gave_ = $out_pay->where('out_status', '=', 'gave')
        //     ->whereIn('payment_type', ['cash_$', 'nation_$'])
        //     ->sum('pay_total');
        // $gave_sum = $out_pay->where('out_status', '=', 'gave')
        //     ->whereIn('payment_type', ['cash_sum', 'nation_sum'])
        //     ->sum('pay_total');
        $convert_sum = $in_pay->whereIn('payment_type', ['cash_$', 'nation_$'])->sum('convert_price');
        $convert_dol = $in_pay->whereIn('payment_type', ['cash_sum', 'nation_sum'])->sum('convert_price');
        $all_dol = (
            $out_pay
                ->whereIn('payment_type', ['cash_$', 'nation_$'])->map(function ($item) {
                return ($item->payment_type == 'cash_$' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total) - $item->exchange_price;
            })->sum())
         -
        $in_pay->whereIn('payment_type', ['cash_$', 'nation_$'])->map(function ($item) {
            return ($item->payment_type == 'cash_$' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total) - $item->exchange_price;
        })->sum() - $convert_dol;
        $all_sum = (
            $out_pay
                ->whereIn('payment_type', ['cash_sum', 'nation_sum'])->map(function ($item) {
                return ($item->payment_type == 'cash_sum' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total) - $item->exchange_price;
            })->sum())
         -
        $in_pay->whereIn('payment_type', ['cash_sum', 'nation_sum'])->map(function ($item) {
            return ($item->payment_type == 'cash_sum' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total) - $item->exchange_price;
        })->sum() - $convert_sum;
        return [
            'id' => $this->id,
            'name' => $this->name,
            // 'data' => $this->inAndOutPayment,
            'result' => [
                'in_payment' =>
                [

                    // '$' => $in_pay->whereIn('payment_type', ['cash_$', 'nation_$'])->sum('pay_total'),
                    '$' => $in_pay->whereIn('payment_type', ['cash_$', 'nation_$'])->map(function ($item) {
                        return ($item->back_pay_total > 0 ? ($item->pay_all_total + $item->initial_price) - $item->back_pay_total : $item->pay_total);
                    })->sum(),
                    'sum' => $in_pay->whereIn('payment_type', ['cash_sum', 'nation_sum'])
                        ->map(function ($item) {
                            return ($item->back_pay_total > 0 ? ($item->pay_all_total + $item->initial_price) - $item->back_pay_total : $item->pay_total);
                        })->sum(),
                    // ->sum('pay_total'),
                ],
                'out_payment' =>
                [
                    '$' => $out_pay->whereIn('payment_type', ['cash_$', 'nation_$'])
                    // ->where('out_status', '=', 'get')
                        ->map(function ($item) {
                            return ($item->back_pay_total > 0 ? ($item->pay_all_total + $item->initial_price) - $item->back_pay_total : $item->pay_total);
                        })->sum() - $gave_,
                    // ->sum('pay_total'),
                    'sum' => $out_pay
                    // ->where('out_status', '=', 'get')
                        ->whereIn('payment_type', ['cash_sum', 'nation_sum'])
                        ->map(function ($item) {
                            return ($item->back_pay_total > 0 ? ($item->pay_all_total + $item->initial_price) - $item->back_pay_total : $item->pay_total);
                        })->sum() - $gave_sum,
                    // ->sum('pay_total'),
                ],
                'debt' => [
                    '$' => $in_pay->where('payment_type', 'nation_$')->map(function ($item) {
                        return ($item->back_pay_total > 0 ? 0 : $item->pay_total - ($item->pay_all_total + $item->initial_price));
                    })->sum(),
                    'sum' => $in_pay->where('payment_type', 'nation_sum')->map(function ($item) {
                        return ($item->back_pay_total > 0 ? 0 : $item->pay_total - ($item->pay_all_total + $item->initial_price));
                    })->sum(),
                ],
                'all_in_pay' => [
                    '$' => $in_pay->whereIn('payment_type', ['cash_$', 'nation_$'])->map(function ($item) {
                        return ($item->payment_type == 'cash_$' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total);
                    })->sum() + $convert_dol,
                    'sum' => $in_pay->whereIn('payment_type', ['cash_sum', 'nation_sum'])->map(function ($item) {
                        return ($item->payment_type == 'cash_sum' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total);
                    })->sum() + $convert_sum,
                ],
                'all_price' => [
                    '$' => $out_pay
                    // ->where('out_status', '=', 'get')
                        ->whereIn('payment_type', ['cash_$', 'nation_$'])->map(function ($item) {
                        return ($item->payment_type == 'cash_$' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total);
                    })->sum() - $gave_,
                    'sum' => $out_pay
                    // ->where('out_status', '=', 'get')
                        ->whereIn('payment_type', ['cash_sum', 'nation_sum'])->map(function ($item) {
                        return ($item->payment_type == 'cash_sum' ? $item->pay_total - $item->back_pay_total : ($item->pay_all_total + $item->initial_price) - $item->back_pay_total);
                    })->sum() - $gave_sum,
                ],

                'convert' => [
                    '$' => $convert_dol,
                    'sum' => $convert_sum,
                ],
                'price' => [
                    'price_$' => $all_dol,
                    'price_sum' => $all_sum,
                ],

            ],
        ];
    }
}
