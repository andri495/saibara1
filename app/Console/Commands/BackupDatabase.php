<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = [
            'users',
            'transactions',
            'transaction_objects',
            'revenues',
            'retribution_targets',
            'master_objek',
            'master_opd',
            'master_lokasi_objek',
            'cart',
            'cart_objects',
            'documents',
            'installment_transactions'
        ];

        foreach ($tables as $table) {

            $this->info("Sync {$table}");

            DB::table($table)
                ->orderBy('id')
                ->chunk(500, function ($chunk) use ($table) {

                    $rows = collect($chunk)->map(function ($row) use ($table) {

                        $row = (array) $row;

                        if ($table === 'revenues') {
                            unset($row['year']);
                            unset($row['month']);
                        }

                        return $row;
                    })->toArray();

                    DB::connection('mysql_backup')
                        ->table($table)
                        ->upsert(
                            $rows,
                            ['id'],
                            array_diff(array_keys($rows[0]), ['id'])
                        );
                });

            $this->info("✓ {$table}");
        }

        $this->info('Backup selesai');
    }
}
