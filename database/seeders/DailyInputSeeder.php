<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Materials;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyInputSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $materials = Materials::all();

        foreach ($materials as $material) {
            $previousStock = rand(30, 70); // Starting stock

            // Create daily inputs for the last 30 days
            for ($day = 29; $day >= 0; $day--) {
                $date = Carbon::now()->subDays($day);

                // Stock fluctuates slightly from previous day (more realistic)
                $change = rand(-10, 10);
                $daily_stock = max(0, min(100, $previousStock + $change));

                // Determine status based on stock levels
                $status = 'OK';
                if ($daily_stock > $material->stock_maximum) {
                    $status = 'OVERFLOW';
                } elseif ($daily_stock == 0) {
                    $status = 'SHORTAGE';
                } elseif ($daily_stock < $material->stock_minimum) {
                    $status = 'CRITICAL';
                }

                DB::table('daily_inputs')->insert([
                    'material_id' => $material->id,
                    'daily_stock' => $daily_stock,
                    'status' => $status,
                    'date' => $date->toDateString(),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                $previousStock = $daily_stock;
            }
        }
    }
}
