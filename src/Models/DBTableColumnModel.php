<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Models;

/**
 * 数据库表字段模型类
 */
class DBTableColumnModel
{
    /**
     * 字段名
     * 比如：id、name、created_at
     */
    public string $name = '';

    /**
     * 字段类型名
     * 比如：bigint、varchar、datetime
     */
    public string $typeName = '';

    /**
     * 字段类型
     * 比如：bigint unsigned、varchar(255)
     */
    public string $type = '';

    /**
     * 字段类型在属性中的表示
     * 比如：bigint 是 numeric
     */
    public string $typeInProperty = '';

    /**
     * 排序规则
     * 用于 char、varchar、text 等类型
     */
    public ?string $collation = null;

    /**
     * 是否可为空
     */
    public bool $nullable = false;

    /**
     * 默认值
     */
    public ?string $default = null;

    /**
     * 是否自增
     */
    public bool $autoIncrement = false;

    /**
     * 字段注释
     */
    public ?string $comment = null;

    /**
     * 生成信息
     * 比如：auto_increment
     */
    public ?string $generation = null;
}
