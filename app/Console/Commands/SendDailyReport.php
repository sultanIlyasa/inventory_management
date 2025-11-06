<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AnonymousPushSubscription;
use App\Services\MaterialReportService;

class SendDailyReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:send-daily-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(MaterialReportService $reportService)
    {
        // Get material counts
        $report = $reportService->getGeneralReport([]);
        $materials = $report['data'];

        $cautionCount = collect($materials)
            ->where('current_status', 'CAUTION')
            ->count();

        $shortageCount = collect($materials)
            ->where('current_status', 'SHORTAGE')
            ->count();

        // Prepare notification payload
        $payload = [
            'title' => 'Daily Material Report',
            'body' => "⚠️ Caution: {$cautionCount} | 🚨 Shortage: {$shortageCount}",
            'icon' => '/pwa-192x192.png',
            'badge' => '/badge-icon.png',
            'tag' => 'daily-report',
            'requireInteraction' => false,
            'data' => [
                'url' => url('/leaderboard'),
                'caution' => $cautionCount,
                'shortage' => $shortageCount,
                'timestamp' => now()->toIso8601String(),
            ],
        ];

        // Send to all subscribers (no user auth needed!)
        AnonymousPushSubscription::sendToAll($payload);

        $subscriberCount = AnonymousPushSubscription::count();
        $this->info("Sent daily report to {$subscriberCount} subscribers");
        $this->info("Caution: {$cautionCount}, Shortage: {$shortageCount}");
    }
}
