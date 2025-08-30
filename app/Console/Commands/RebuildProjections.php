<?php

namespace App\Console\Commands;

use App\Projectors\ProjectorRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'projections:rebuild', description: 'Rebuild read models by replaying the event store')]
class RebuildProjections extends Command
{
    public function __construct(private ProjectorRegistry $registry)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->confirm('This will truncate read tables and replay all events. Continue?')) {
            $this->warn('Aborted.');
            return self::SUCCESS;
        }

        $this->info('Truncating read tables…');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('product_reads')->truncate();
        DB::table('inventory_reads')->truncate();
        DB::table('order_reads')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $count = DB::table('event_store')->count();
        $this->info("Replaying {$count} events…");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        // Stream by id to avoid memory blow-ups
        DB::table('event_store')->orderBy('id')
            ->chunkById(1000, function ($rows) use ($bar) {
                foreach ($rows as $row) {
                    $payload = json_decode($row->payload, true) ?? [];
                    $this->registry->dispatch($row->event_name, $payload);
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Projections rebuilt successfully.');
        return self::SUCCESS;
    }
}
