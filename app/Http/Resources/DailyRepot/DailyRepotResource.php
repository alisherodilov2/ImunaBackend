<?php

namespace App\Http\Resources\DailyRepot;

use App\Models\ClientBalance;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyRepotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $dailyBalance = $this->status != 'finish' ?  ClientBalance::where('daily_repot_id', $this->id)
            ->whereDate('status', 'pay')
            ->get() : collect([]);
        return  [
            'id' => $this->id,
            'user' => $this->user,
            'batch_number' => $this->batch_number,
            'cash_price' => $this->cash_price +      $dailyBalance->where('pay_type', 'cash')->sum('price') ?? 0,
            'card_price' => $this->card_price +      $dailyBalance->where('pay_type', 'card')->sum('price') ?? 0,
            'transfer_price' => $this->transfer_price +      $dailyBalance->where('type', 'transfer')->sum('price') ?? 0,
            'total_price' => $this->total_price +      $dailyBalance->sum('price') ?? 0,
            'status' => $this->status,
            'count' => $this->dailyRepotClient->count(),
            'expence' => $this->dailyRepotExpense?->sum(function ($q) {
                return $q->expense->price ?? 0;
            }) ?? 0,
            'current_date' => $this->created_at->format('Y-m-d'),
        ];
    }
}
