<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Models;

use Illuminate\Support\Collection;

/**
 * 数据库表模型类
 */
class DBModel
{
    /**
     * 表名
     * 比如：admins、users、orders
     */
    public array $tableNames = [];

    /**
     * 关联表
     */
    public array $belongsTo = [];

    /**
     * 关联表
     */
    public array $hasMany = [];

    /**
     * 表集合
     */
    public Collection $tables;

    public function __construct()
    {
        $this->tables = collect();
    }
}
