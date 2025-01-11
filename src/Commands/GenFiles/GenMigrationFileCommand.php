<?php

namespace Zuoge\LaravelToolsAi\Commands\GenFiles;

use Illuminate\Console\Command;

class GenMigrationFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gm {table_name}';

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
        $tableName = str()->of($this->argument('table_name'))
            ->snake()
            ->singular()
            ->plural();

        $this->call('make:migration', [
            'name' => "create_{$tableName}_table",
            '--create' => $tableName,
            '--table' => $tableName,
        ]);
    }
}
