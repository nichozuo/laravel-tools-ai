<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Collections;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Zuoge\LaravelToolsAi\Models\DBTableColumnModel;
use Zuoge\LaravelToolsAi\Models\DBTableModel;

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
     * @var Collection|null
     */
    protected ?Collection $collection = null;

    protected array $dbCollectionConfig = [];

    /**
     * 构造函数
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
    public function getCollection(): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->init();
        }
        return $this->collection;
    }

    /**
     * 初始化数据库表集合
     * 获取数据库中所有表的信息
     */
    protected function init(): Collection
    {
        $collection = collect();

        // 获取所有表名
        $tables = Schema::getTables();

        // 遍历每个表
        foreach ($tables as $table) {
            if (!in_array($table['name'], $this->dbCollectionConfig['skip_tables'] ?? [])) {
                $collection->push($this->parseTable($table));
            }
        }

        return $collection;
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
        $model->foreignKeys = Schema::getForeignKeys($table['name']);
        $model->indexes = Schema::getIndexes($table['name']);

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
     */
    protected function getTypeInProperty(string $typeName): string
    {
        switch ($typeName) {
            case 'bigint':
            case 'int':
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
                return 'numeric';
            default:
                return 'string';
        }
    }
}
