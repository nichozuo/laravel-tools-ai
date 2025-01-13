<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Collections;

use Illuminate\Support\Facades\Schema;
use Zuoge\LaravelToolsAi\Models\DBModel;
use Zuoge\LaravelToolsAi\Models\DBTableColumnModel;
use Zuoge\LaravelToolsAi\Models\DBTableModel;
use Illuminate\Support\Str;

/**
 * 数据库表集合类
 *
 * 用于收集和管理数据库表信息，主要功能：
 * 1. 获取数据库中所有表的信息
 * 2. 解析表的结构，包括字段、索引、外键等
 * 3. 生成标准化的表模型对象
 *
 * 使用单例模式确保全局只有一个实例，减少内存占用
 * 使用懒加载模式，只在首次获取数据时初始化集合
 */
class DBTableCollection
{
    /**
     * 存储唯一实例
     * @var self|null
     */
    protected static ?self $instance = null;

    /**
     * 内存中的集合数据
     * @var DBModel|null
     */
    protected ?DBModel $dbModel = null;

    /**
     * 数据库集合配置
     * @var array
     */
    protected array $dbCollectionConfig = [];

    /**
     * 构造函数
     * 初始化数据库集合配置
     */
    protected function __construct()
    {
        // 初始化 dbCollectionConfig
        $this->dbCollectionConfig = config('common.db_collection', []);
    }

    /**
     * 获取单例实例
     * 确保全局只有一个 DBTableCollection 实例
     */
    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * 获取数据库表集合数据
     * 使用懒加载模式，仅在首次调用时初始化数据
     */
    public function getCollection(): DBModel
    {
        if ($this->dbModel === null) {
            $this->dbModel = $this->init();
        }
        return $this->dbModel;
    }

    /**
     * 初始化数据库表集合
     * 获取数据库中所有表的信息
     */
    protected function init(): DBModel
    {
        $dbModel = new DBModel();

        // 获取所有表名
        $tables = Schema::getTables();
        $dbModel->tableNames = array_column($tables, 'name');

        // 遍历每个表
        foreach ($tables as $table) {
            // 跳过配置中指定的表
            if (!in_array($table['name'], $this->dbCollectionConfig['skip_tables'] ?? [])) {
                $dbModel->tables->push($this->parseTable($table));
            }
        }

        // 解析表之间的关系
        $this->parseRelations($dbModel);

        return $dbModel;
    }

    /**
     * 解析表结构
     * 将表结构转换为标准的 DBTableModel 对象
     */
    protected function parseTable(array $table): DBTableModel
    {
        $model = new DBTableModel();
        $model->name = $table['name'] ?? '';
        $model->comment = $table['comment'] ?? '';
        $model->indexes = Schema::getIndexes($table['name']);
        // $model->foreignKeys = Schema::getForeignKeys($table['name']);

        // 获取表的所有字段
        $columns = Schema::getColumns($table['name']);
        foreach ($columns as $column) {
            $model->columns->push($this->parseColumn($column));
        }

        // 从构造函数中初始化的配置中读取并设置新增的字段
        $model->hasApiToken = in_array($table['name'], $this->dbCollectionConfig['has_api_token'] ?? []);
        $model->hasRoles = in_array($table['name'], $this->dbCollectionConfig['has_roles'] ?? []);
        $model->hasNodeTrait = in_array($table['name'], $this->dbCollectionConfig['has_node_trait'] ?? []);

        return $model;
    }

    /**
     * 解析字段结构
     * 将字段结构转换为标准的 DBTableColumnModel 对象
     */
    protected function parseColumn(array $column): DBTableColumnModel
    {
        $model = new DBTableColumnModel();

        $model->name = $column['name'];
        $model->typeName = $column['type_name'];
        $model->type = $column['type'];
        $model->typeInProperty = $this->getTypeInProperty($column['type_name']);
        $model->collation = $column['collation'] ?? null;
        $model->nullable = $column['nullable'];
        $model->default = $column['default'] ?? null;
        $model->autoIncrement = (bool) ($column['auto_increment'] ?? false);
        $model->comment = $column['comment'] ?? null;
        $model->generation = $column['generation'] ?? null;

        return $model;
    }

    /**
     * 获取字段类型在属性中的表示
     * 根据字段类型名称返回对应的属性类型
     */
    protected function getTypeInProperty(string $typeName): string
    {
        return match ($typeName) {
            'bigint', 'int', 'tinyint', 'smallint', 'mediumint' => 'numeric',
            default => 'string',
        };
    }

    /**
     * 解析表之间的关系
     * 根据字段名和类型推断表之间的关系
     */
    protected function parseRelations(DBModel $dbModel): void
    {
        // 解析表之间的关系
        foreach ($dbModel->tables as $table) {
            foreach ($table->columns as $column) {
                // 检查字段名是否以 '_id' 结尾且类型为 'bigint'
                if (Str::endsWith($column->name, '_id') && $column->typeName === 'bigint') {
                    $oneTableName = Str::of($column->name)->replace('_id', '')->singular()->plural();
                    if (!in_array($oneTableName->toString(), $dbModel->tableNames)) {
                        continue;
                    }
                    $manyTableName = Str::of($table->name);

                    // 添加 belongsTo 关系
                    $dbModel->belongsTo[$table->name][] = [
                        'name' => $oneTableName->singular()->toString(),
                        'related' => $oneTableName->camel()->ucfirst()->toString() . '::class',
                        'foreignKey' => $column->name,
                        'ownerKey' => 'id',
                    ];

                    // 添加 hasMany 关系
                    $dbModel->hasMany[$oneTableName->toString()][] = [
                        'name' => $manyTableName->toString(),
                        'related' => $manyTableName->camel()->ucfirst()->toString() . '::class',
                        'foreignKey' => $column->name,
                        'localKey' => 'id',
                    ];

                    // 调试输出
                    // dd($dbModel->belongsTo, $dbModel->hasMany);
                }
            }
        }
    }
}
