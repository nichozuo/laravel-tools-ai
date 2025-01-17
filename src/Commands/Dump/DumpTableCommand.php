<?php

namespace Zuoge\LaravelToolsAi\Commands\Dump;

use Exception;
use Illuminate\Console\Command;
use Zuoge\LaravelToolsAi\Collections\DBTableCollection;

class DumpTableCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'dt {table_name}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = '生成指定表的一些属性';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        $tableName = str()->of($this->argument('table_name'))
            ->snake()
            ->singular()
            ->plural();

        $table = DBTableCollection::getInstance()->getByName($tableName);

        $this->warn('Gen Table template');
        $this->line($table->getTable());
        $this->line($table->getComment());
        $this->line($table->getFillable());

        $this->warn('gen Validate template');
        $this->line($table->getValidateString("\n"));

        $this->warn('gen Insert template');
        $this->line($table->getValidateString("\n", true));
    }
}
