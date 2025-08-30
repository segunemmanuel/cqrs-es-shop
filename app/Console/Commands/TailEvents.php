<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'events:tail',
    description: 'Continuously tail the event_store table'
)]
class TailEvents extends Command
{
    public function handle(): int
    {
        $this->info("Tailing events... (Ctrl+C to quit)");

        $lastId = DB::table('event_store')->max('id') ?? 0;

        while (!feof(STDIN)) {
            $rows = DB::table('event_store')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                $this->line(sprintf(
                    "[%s] #%d %s (%s v%s): %s",
                    $row->occurred_at,
                    $row->id,
                    $row->event_name,
                    $row->aggregate_id,
                    $row->version,
                    $row->payload
                ));

                $lastId = $row->id;
            }

            // small sleep to avoid hammering DB
            usleep(500000); // 0.5s

            // Check for Ctrl+C (SIGINT)
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }

        return self::SUCCESS;
    }
}
