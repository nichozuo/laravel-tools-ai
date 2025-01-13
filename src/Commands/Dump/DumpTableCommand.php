<?php

namespace Zuoge\LaravelToolsAi\Commands\Dump;

use Exception;
use Illuminate\Console\Command;
use Zuoge\LaravelToolsAi\Collections\DBTableCollection;
use Zuoge\LaravelToolsAi\Models\DBTableModel;

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

        // $className = str()->of($tableName)->studly();

        // $namespace = 'App\\Models\\' . $className;

        // 2. 获取表信息
        $dbModel = DBTableCollection::getInstance()->getCollection();
        $table = $dbModel->tables->where('name', $tableName)->first();
        if (!$table)
            ee("Table $tableName not found!");

        $this->warn('Gen Table template');
        $this->line("protected \$table = '$table->name';");
        $this->line("protected string \$comment = '$table->comment';");
        $this->line("protected \$fillable = [{$this->getFillable($table)}];");

        $this->warn('gen Validate template');
        $this->line($this->getValidates($table));

        $this->warn('gen Insert template');
        $this->line($this->getInserts($table));
    }

    protected function getFillable(DBTableModel $table): string
    {
        $fillable = [];
        foreach ($table->columns as $column) {
            if (!in_array($column->name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $fillable[] = "'$column->name'";
            }
        }
        return implode(', ', $fillable);
    }

    protected function getValidates(DBTableModel $table): string
    {
        $validateString = [];
        $skipColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];
        foreach ($table->columns as $column) {
            if (in_array($column->name, $skipColumns))
                continue;

            // 是否可空：1是否nullable，2是否有default
            $required = $column->nullable || $column->default ? 'nullable' : 'required';
            $validateString[] = "'$column->name' => '$required|$column->typeInProperty', # $column->comment";
        }
        return implode("\n", $validateString);
    }

    protected function getInserts(DBTableModel $table): string
    {
        $validateString = [];
        $skipColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];
        foreach ($table->columns as $column) {
            if (in_array($column->name, $skipColumns))
                continue;

            $validateString[] = "'$column->name' => '', # $column->comment";
        }
        return implode("\n", $validateString);
    }
}
