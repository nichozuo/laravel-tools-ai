<?php

namespace Zuoge\LaravelToolsAi\Commands\GenFiles;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Zuoge\LaravelToolsAi\Collections\DBTableCollection;
use Zuoge\LaravelToolsAi\Models\DBTableModel;
use Illuminate\Support\Str;

class GenControllerFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gc {name} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "根据输入的路径，生成控制器文件。路径通过斜杠/拆分成[模块名]和[表名]。
    模块名：会转成大写开头的驼峰，斜杠/分割成数组，支持多级目录；
    表名：转蛇形；
    例如：php artisan gc admin/users
    例如：php artisan gc Admin/auth/CompanyAdmins";

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        $name = $this->argument('name');
        if (!$name)
            ee("名称必须填写");

        $force = $this->option('force');

        $modulesName = Str::of($name)->explode('/')->map(function ($item) {
            return Str::of($item)->studly()->toString();
        })->toArray();

        $tableName = Str::of(array_pop($modulesName))->snake()->singular()->plural()->toString();

        $modelName = Str::of($tableName)->studly()->toString();

        $namespace = 'App\\Modules\\' . implode('\\', $modulesName);

        $filePath = app_path('Modules/' . implode('/', $modulesName) . '/' . $modelName . 'Controller.php');
        if (File::exists($filePath) && !$force)
            ee("文件已存在，如需覆盖，请加 --force 参数");

        $dbModel = DBTableCollection::getInstance()->getCollection();

        $table = $dbModel->tables->where('name', $tableName)->first();
        if (!$table)
            ee("Table $tableName not found!");

        // 3. 生成基础模型文件
        $this->generateControllerFile($table, $namespace, $modelName, $filePath);

        $this->info($filePath);
    }

    private function generateControllerFile(DBTableModel $table, string $namespace, string $modelName, string $filePath): void
    {
        // 读取控制器模板
        $stub = File::get(__DIR__ . '/stubs/controller.stub');
        $validateString = $this->getValidateString($table);

        // 替换内容
        $content = str_replace(
            [
                '{{ namespace }}',
                '{{ modelName }}',
                '{{ comment }}',
                '{{ validateString }}'
            ],
            [
                $namespace,
                $modelName,
                $table->name,
                $validateString
            ],
            $stub
        );

        // 确保目录存在
        // $directory = app_path('Modules/' . str_replace('\\', '/', $namespace));
        // if (!File::exists($directory)) {
        //     File::makeDirectory($directory, 0755, true);
        // }

        // 写入文件
        File::put($filePath, $content);
    }

    private function getValidateString(DBTableModel $table): string
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
        return implode("\n\t\t\t", $validateString);
    }
}
