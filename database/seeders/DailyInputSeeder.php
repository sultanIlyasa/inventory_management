<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Materials;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyInputSeeder extends Seeder
{
    public function run(): void
    {
        $materials = Materials::select(
            'id',
            'stock_minimum',
            'stock_maximum'
        )->get();

        $batch = [];
        $batchSize = 500;

        foreach ($materials as $material) {
            $previousStock = rand(30, 70);

            for ($day = 180 ; $day >= 0; $day--) {
                $date = Carbon::now()->subDays($day);

                $change = rand(-5, 5);
                $daily_stock = max(0, min(
                    $material->stock_maximum + 10,
                    $previousStock + $change
                ));

                // Status logic
                if ($daily_stock > $material->stock_maximum) {
                    $status = 'OVERFLOW';
                } elseif ($daily_stock === 0) {
                    $status = 'SHORTAGE';
                } elseif ($daily_stock < $material->stock_minimum) {
                    $status = 'CAUTION';
                } else {
                    $status = 'OK';
                }

                $batch[] = [
                    'material_id' => $material->id,
                    'daily_stock' => $daily_stock,
                    'status' => $status,
                    'date' => $date->toDateString(),
                    'created_at' => $date,
                    'updated_at' => $date,
                ];

                // Insert batch
                if (count($batch) >= $batchSize) {
                    DB::table('daily_inputs')->insert($batch);
                    $batch = []; // free memory
                }

                $previousStock = $daily_stock;
            }
        }

        // Insert leftovers
        if (!empty($batch)) {
            DB::table('daily_inputs')->insert($batch);
        }
    }
}
