<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'material_number' => $this->material_number,
            'description' => $this->description,
            'pic_name' => $this->pic_name,
            'stock_minimum' => $this->stock_minimum,
            'stock_maximum' => $this->stock_maximum,
            'unit_of_measure' => $this->unit_of_measure,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            // eager load last daily check
            'latest_check' => $this->dailyInputs()->latest('date')->first()?->only([
                'date',
                'daily_stock',
                'status'
            ])
        ];;
    }
}
