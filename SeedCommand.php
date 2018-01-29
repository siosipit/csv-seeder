<?php

namespace SeedCommand;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'me:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed databases with CSV files for each environment.';

    /**
     * Execute the console command:
     *
     * @return void
     */
    public function handle()
    {
        $path = base_path('database'.DIRECTORY_SEPARATOR.'seeds').DIRECTORY_SEPARATOR;
        $envs = glob($path.'*', GLOB_ONLYDIR);
        array_walk($envs, function(&$v) {
            $allDirs = explode(DIRECTORY_SEPARATOR, $v);
            $v = array_pop($allDirs);
        });

        if(in_array(app()->environment(), $envs)) {
            $this->info('Seeding environment: '.app()->environment());

            $connectionDirs = $path.app()->environment().DIRECTORY_SEPARATOR;
            $connections = glob($connectionDirs.'*', GLOB_ONLYDIR);
            array_walk($connections, function(&$v) {
                $allDirs = explode(DIRECTORY_SEPARATOR, $v);
                $v = array_pop($allDirs);
            });

            foreach($connections as $connection) {
                $this->comment('Seeding DB connection: '.$connection);
                $csvDir = $connectionDirs.$connection.DIRECTORY_SEPARATOR;
                $csvs = glob($csvDir.'*.csv');
                array_walk($csvs, function(&$v) {
                    $allCsvs = explode(DIRECTORY_SEPARATOR, $v);
                    $v = array_pop($allCsvs);
                });
                arsort($csvs); // sort by DESC so most recent CSVs will run first (seed files named like migrations)
                $seededTables = [];

                DB::connection($connection)->statement("SET FOREIGN_KEY_CHECKS = 0");

                foreach($csvs as $csv) {
                    $table = substr(substr($csv, strpos($csv, '-') + 1), 0, -4); // still has .csv on the end
                    if(!in_array($table, $seededTables)) {
                        try {
                            $comment = DB::connection($connection)->select('SELECT TABLE_COMMENT FROM
                              information_schema.TABLES WHERE TABLE_NAME = "'.$table.'"
                              AND TABLE_SCHEMA ="'.$connection.'"');
                            $priorSeededCsv = count($comment) ? @explode(' ', @$comment[0]->TABLE_COMMENT)[2] : '';
                            if ($csv != $priorSeededCsv) {
                                $pdo = DB::connection($connection)->getpdo();
                                $pdo->exec("TRUNCATE ".$table.";");

                                $csvFile = $csvDir.$csv;
                                if (DIRECTORY_SEPARATOR!='/') {
                                    $csvFile = str_replace('\\', '/', $csvFile);
                                }
                                $query = sprintf("LOAD DATA local INFILE '%s' INTO TABLE ".$connection.".".$table." FIELDS
                                TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' ESCAPED BY '\"' LINES TERMINATED BY
                                '\\n'", $csvFile);
                                DB::connection($connection)->getpdo()->exec($query);
                                DB::connection($connection)->getpdo()->exec('ALTER TABLE '.$table.' COMMENT="seeded by '
                                    .$csv.' on '.date('Y-m-d h:i:s').'"');
                                $seededTables[] = $table;
                                $this->line('Seeded table: '.$table.' with '.$csv);
                            } else {
                                $seededTables[] = $table;
                                $this->line('Did not seed table: '.$table);
                            }
                        } catch(\Exception $e) {
                            $this->line('Error seeding: '.$table.' with file: '.$csv);
                        }
                    }
                }
                DB::connection($connection)->statement("SET FOREIGN_KEY_CHECKS = 1");
            }
        } else {
            $this->info('No seeds for environment: '.app()->environment());
        }
    }
}
