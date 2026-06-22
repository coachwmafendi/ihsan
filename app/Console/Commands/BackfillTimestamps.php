<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTimestamps extends Command
{
    protected $signature = 'app:backfill-timestamps
                            {--hours=8 : Hours to add to timestamp columns}
                            {--dry-run : Preview changes without updating}
                            {--force : Skip confirmation prompts}
                            {--tables= : Comma-separated list of tables to process (default: all)}';

    protected $description = 'Add hours to all timestamp/timestamptz columns to correct timezone drift.';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');
        $requestedTables = $this->option('tables');

        $driver = DB::getDriverName();

        if (! in_array($driver, ['pgsql', 'mysql', 'sqlite'], true)) {
            $this->error("Unsupported database driver: {$driver}");

            return self::FAILURE;
        }

        $tables = $requestedTables
            ? array_map('trim', explode(',', $requestedTables))
            : $this->getTimestampTables($driver);

        if ($tables === []) {
            $this->warn('No tables found with timestamp columns.');

            return self::SUCCESS;
        }

        $this->info('Tables to process: '.implode(', ', $tables));

        foreach ($tables as $table) {
            $columns = $this->getTimestampColumns($table, $driver);

            if ($columns === []) {
                $this->warn("No timestamp columns in table {$table}.");

                continue;
            }

            foreach ($columns as $column) {
                $count = $this->getAffectedCount($table, $column);

                if ($count === 0) {
                    continue;
                }

                $this->info("{$table}.{$column}: {$count} rows");

                if ($dryRun) {
                    continue;
                }

                if (! $this->option('force') && ! $this->confirm("Update {$table}.{$column} by +{$hours} hours?")) {
                    continue;
                }

                $this->updateColumn($table, $column, $hours, $driver);
            }
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Backfill complete.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function getTimestampTables(string $driver): array
    {
        if ($driver === 'sqlite') {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");

            return array_map(fn ($row) => $row->name, $rows);
        }

        $rows = DB::select(
            'SELECT DISTINCT table_name FROM information_schema.columns WHERE table_schema = current_schema() AND data_type IN (?, ?)',
            ['timestamp with time zone', 'timestamp without time zone']
        );

        return array_map(fn ($row) => $row->table_name, $rows);
    }

    /**
     * @return array<int, string>
     */
    private function getTimestampColumns(string $table, string $driver): array
    {
        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA table_info({$table})");

            return array_values(array_filter(array_map(function ($row) {
                $type = strtolower($row->type);

                return str_contains($type, 'timestamp') || str_contains($type, 'datetime') ? $row->name : null;
            }, $rows)));
        }

        $rows = DB::select(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND data_type IN (?, ?) ORDER BY ordinal_position',
            [$table, 'timestamp with time zone', 'timestamp without time zone']
        );

        return array_map(fn ($row) => $row->column_name, $rows);
    }

    private function getAffectedCount(string $table, string $column): int
    {
        return (int) DB::table($table)->whereNotNull($column)->count();
    }

    private function updateColumn(string $table, string $column, int $hours, string $driver): void
    {
        $progress = $this->output->createProgressBar();
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');

        $total = $this->getAffectedCount($table, $column);
        $progress->setMaxSteps($total);

        $updated = 0;
        $chunkSize = 1000;
        $lastId = 0;

        do {
            $ids = DB::table($table)
                ->whereNotNull($column)
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($chunkSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $wrappedTable = DB::getQueryGrammar()->wrapTable($table);
            $wrappedColumn = DB::getQueryGrammar()->wrap($column);
            $wrappedId = DB::getQueryGrammar()->wrap('id');

            $expression = $driver === 'mysql'
                ? "DATE_ADD({$wrappedColumn}, INTERVAL {$hours} HOUR)"
                : "{$wrappedColumn} + INTERVAL '{$hours} hours'";

            $placeholders = implode(',', array_fill(0, $ids->count(), '?'));

            $count = DB::update(
                "UPDATE {$wrappedTable} SET {$wrappedColumn} = {$expression} WHERE {$wrappedColumn} IS NOT NULL AND {$wrappedId} IN ({$placeholders})",
                $ids->toArray()
            );

            $updated += $count;
            $lastId = $ids->last();
            $progress->setProgress($updated);
        } while ($ids->count() === $chunkSize);

        $progress->finish();
        $this->newLine();
        $this->info("Updated {$updated} rows in {$table}.{$column}");
    }
}
