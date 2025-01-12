<?php

namespace Zuoge\LaravelToolsAi\Commands\GenFiles;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Zuoge\LaravelToolsAi\Collections\DBTableCollection;
use Zuoge\LaravelToolsAi\Models\DBTableModel;

class GenModelFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gd {table_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成数据库表对应的模型文件';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. 获取表名并处理
        $tableName = str()->of($this->argument('table_name'))
            ->snake()
            ->singular()
            ->plural();

        $className = str()->of($tableName)->studly();

        // 2. 获取表信息
        $collection = DBTableCollection::getInstance()->getCollection();
        $table = $collection->where('name', $tableName)->first();
        if (!$table) {
            $this->error("Table {$tableName} not found!");
            return;
        }

        // 3. 生成基础模型文件
        $this->generateModelFile($className, $table);

        $this->info("Model files generated successfully!");
    }

    private function generateModelFile(string $className, DBTableModel $table): void
    {
        // 准备数据
        $importClasses = $this->getImportClasses($table);
        $useTraits = $this->getUseTraits($table);
        $properties = $this->getProperties($table);
        $fillable = $this->getFillable($table);
        $hidden = $this->getHidden($table);
        $casts = $this->getCasts($table);
        $relations = $this->getRelations($table);

        // 读取基础模型模板
        $stub = File::get(__DIR__ . '/stubs/model.base.stub');

        // 替换内容
        $content = str_replace(
            [
                '{{ importClasses }}',
                '{{ useTraits }}',
                '{{ className }}',
                '{{ tableName }}',
                '{{ comment }}',
                '{{ properties }}',
                '{{ fillable }}',
                '{{ hidden }}',
                '{{ casts }}',
                '{{ relations }}'
            ],
            [
                $importClasses,
                $useTraits,
                $className,
                $table->name,
                $table->comment,
                $properties,
                $fillable,
                $hidden,
                $casts,
                $relations
            ],
            $stub
        );

        // 确保目录存在
        $directory = app_path('Models');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // 写入文件
        File::put($directory . '/' . $className . '.php', $content);
    }

    /**
     * 获取需要导入的类
     */
    private function getImportClasses(DBTableModel $table)
    {
        $importClasses = [];
        if ($table->hasApiToken) {
            $importClasses[] = 'use Laravel\Sanctum\HasApiTokens;';
        }
        if ($table->hasRoles) {
            $importClasses[] = 'use Spatie\Permission\Traits\HasRoles;';
        }
        if ($table->hasNodeTrait) {
            $importClasses[] = 'use Kalnoy\Nestedset\NodeTrait;';
        }
        return implode("\n", $importClasses);
    }

    /** 
     * 获取需要使用的Trait
     */
    private function getUseTraits(DBTableModel $table)
    {
        $useTraits = [];
        if ($table->hasApiToken) {
            $useTraits[] = 'use HasApiTokens;';
        }
        if ($table->hasRoles) {
            $useTraits[] = 'use HasRoles;';
        }
        if ($table->hasNodeTrait) {
            $useTraits[] = 'use NodeTrait;';
        }
        return implode("\n\t", $useTraits);
    }

    /**
     * 获取模型属性
     */
    private function getProperties(DBTableModel $table)
    {
        $properties = '';
        foreach ($table->columns as $column) {
            $properties .= " * @property {$column->typeInProperty} \${$column->name} {$column->comment}\n";
        }
        return $properties;
    }

    /**
     * 获取可填充字段
     */
    private function getFillable(DBTableModel $table)
    {
        $fillable = [];
        foreach ($table->columns as $column) {
            if (!in_array($column->name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $fillable[] = "'{$column->name}'";
            }
        }
        return implode(', ', $fillable);
    }

    /**
     * 获取隐藏字段
     */
    private function getHidden(DBTableModel $table)
    {
        // 在字段的comment中有hidden字样的
        $hidden = [];
        foreach ($table->columns as $column) {
            if (str_contains($column->comment, 'hidden')) {
                $hidden[] = "'{$column->name}'";
            }
        }
        
        if (empty($hidden)) {
            return '';
        }
        
        return "protected \$hidden = [" . implode(', ', $hidden) . "];";
    }

    /**
     * 获取类型转换
     */
    private function getCasts(DBTableModel $table)
    {
        $casts = [];
        foreach ($table->columns as $column) {
            if ($column->type === 'json') {
                $casts[] = "'{$column->name}' => 'array'";
            } elseif ($column->type === 'datetime' || $column->type === 'timestamp') {
                $casts[] = "'{$column->name}' => 'datetime'";
            }
            // TODO 如果是Enum类型，则需要获取Enum的值
        }
        
        if (empty($casts)) {
            return '';
        }
        
        return "protected \$casts = [\n\t\t" . implode(",\n\t\t", $casts) . "\n\t];";
    }

    private function getRelations($table)
    {
        // TODO: 根据外键关系生成关联方法
        return '';
    }
}
