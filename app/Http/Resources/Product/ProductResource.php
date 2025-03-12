<?php

namespace App\Http\Resources\Product;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $productReceptionItem = $this->productReceptionItem->filter(function ($item) {
            return isset($item->productReception->id);
        });
        $totalQty = $productReceptionItem->sum('qty');

        $today = Carbon::today();
        $alert_dedline_day = $this->alert_dedline_day;
        $fiveDaysLater = $today->copy()->subDays($this->alert_dedline_day);
        // $fiveDaysLater = $today->copy()->addDays($this->expiration_day);
        $danger_qty =  $productReceptionItem->filter(function ($item) use ($today,$alert_dedline_day ) {
            $daysRemaining = $today->diffInDays(Carbon::parse($item->expiration_date), false);
    
            // Faqat 5 kundan kichik yoki teng bo'lganlarini qaytaramiz
            return $daysRemaining >= 0 && $daysRemaining <= $alert_dedline_day;
        })->sum('qty');
        return [
            'id' => $this?->id,
            'name' => $this->name,
            'price' => $this->price,
            'prodcut_category' => $this?->prodcutCategory,
            'qty' => $totalQty,
            'use_qty' => $productReceptionItem->sum('use_qty'),
            'alert_min_qty' => $this->alert_min_qty,
            'expiration_day' => $this->expiration_day,
            'alert_dedline_day' => $this->alert_dedline_day,
            'danger_qty' => $danger_qty
        ];
    }
}
