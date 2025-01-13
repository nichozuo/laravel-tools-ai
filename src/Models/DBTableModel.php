<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Models;

use Illuminate\Support\Collection;

/**
 * 数据库表模型类
 */
class DBTableModel
{
    /**
     * 表名
     * 比如：admins、users、orders
     */
    public string $name = '';

    /**
     * 表注释
     * 比如：管理员表、用户表、订单表
     */
    public string $comment = '';

    /**
     * 表是否包含 api token
     */
    public bool $hasApiToken = false;

    /**
     * 表是否包含角色
     */
    public bool $hasRoles = false;

    /**
     * 表是否包含节点
     */
    public bool $hasNodeTrait = false;

    /**
     * 表的所有字段集合
     * @var Collection<DBTableColumnModel>
     */
    public Collection $columns;

    /**
     * 索引集合
     */
    public array $indexes = [];

    public function __construct()
    {
        $this->columns = collect();
    }
}
