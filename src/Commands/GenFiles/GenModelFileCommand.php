<?php

namespace Zuoge\LaravelToolsAi\Commands\GenFiles;

use Zuoge\LaravelToolsAi\Collections\DBTableCollection;
use Zuoge\LaravelToolsAi\Models\DBModel;
use Zuoge\LaravelToolsAi\Models\DBTableModel;

class GenModelFileCommand extends BaseGenFileCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'gd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "根据输入的数据库表名，生成模型文件。
    表名：会转成蛇形，单数，复数。
    例如：php artisan gd users
    例如：php artisan gd User";

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        list($name, $force) = $this->getNameAndForce();

        $tableName = str()->of($name)->snake()->singular()->plural();

        $className = str()->of($tableName)->studly();

        $namespace = 'App\\Models';

        $dbModel = DBTableCollection::getInstance()->getCollection();
        $table = DBTableCollection::getInstance()->getByName($tableName);

        $replaces = [
            '{{ importClasses }}' => $this->getImportClasses($table),
            '{{ useTraits }}' => $this->getUseTraits($table),
            '{{ className }}' => $className,
            '{{ tableName }}' => $table->name,
            '{{ comment }}' => $table->comment,
            '{{ properties }}' => $this->getProperties($table),
            '{{ fillable }}' => $this->getFillable($table),
            '{{ hidden }}' => $this->getHidden($table),
            '{{ casts }}' => $this->getCasts($table),
            '{{ relations }}' => $this->getRelations($dbModel, $table),
        ];

        $this->GenFile('model.stub', $replaces, $namespace, $className, $force);
    }

    /**
     * 获取需要导入的类
     */
    private function getImportClasses(DBTableModel $table): string
    {
        $importClasses = ['use Illuminate\Database\Eloquent\Relations;'];

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
    private function getUseTraits(DBTableModel $table): string
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
    private function getProperties(DBTableModel $table): string
    {
        $properties = [];
        foreach ($table->columns as $column) {
            $properties[] = " * @property $column->typeInProperty \$$column->name $column->comment";
        }
        return implode("\n", $properties);
    }

    /**
     * 获取可填充字段
     */
    private function getFillable(DBTableModel $table): string
    {
        $fillable = [];
        foreach ($table->columns as $column) {
            if (!in_array($column->name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $fillable[] = "'$column->name'";
            }
        }
        return implode(', ', $fillable);
    }

    /**
     * 获取隐藏字段
     */
    private function getHidden(DBTableModel $table): string
    {
        // 在字段的comment中有hidden字样的
        $hidden = [];
        foreach ($table->columns as $column) {
            if (str_contains($column->comment, 'hidden')) {
                $hidden[] = "'$column->name'";
            }
        }

        if (empty($hidden)) {
            return '';
        }

        return "protected \$hidden = [\n\t\t" . implode(",\n\t\t", $hidden) . "\n\t];";
    }

    /**
     * 获取类型转换
     */
    private function getCasts(DBTableModel $table): string
    {
        $casts = [];
        foreach ($table->columns as $column) {
            if ($column->type === 'json') {
                $casts[] = "'$column->name' => 'array'";
            } elseif ($column->type === 'datetime' || $column->type === 'timestamp') {
                $casts[] = "'$column->name' => 'datetime'";
            }
            // TODO 如果是Enum类型，则需要获取Enum的值
        }

        if (empty($casts)) {
            return '';
        }

        return "protected \$casts = [\n\t\t" . implode(",\n\t\t", $casts) . "\n\t];";
    }

    private function getRelations(DBModel $dBModel, DBTableModel $table): string
    {
        $relations = [];

        $belongsTo = $dBModel->belongsTo[$table->name] ?? null;
        if ($belongsTo) {
            foreach ($belongsTo as $relation) {
                $relations[] = "public function {$relation['name']}(): Relations\BelongsTo\n\t{\n\t\treturn \$this->belongsTo({$relation['related']}, '{$relation['foreignKey']}', '{$relation['ownerKey']}');\n\t}";
            }
        }

        $hasMany = $dBModel->hasMany[$table->name] ?? null;
        if ($hasMany) {
            foreach ($hasMany as $relation) {
                $relations[] = "public function {$relation['name']}(): Relations\HasMany\n\t{\n\t\treturn \$this->hasMany({$relation['related']}, '{$relation['foreignKey']}', '{$relation['localKey']}');\n\t}";
            }
        }

        return implode("\n\t", $relations);
    }
}
