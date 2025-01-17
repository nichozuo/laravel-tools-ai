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

    public function getTable(): string
    {
        return "protected \$table = '$this->name';";
    }

    public function getComment(): string
    {
        return "protected string \$comment = '$this->comment';";
    }

    public function getFillable(): string
    {
        $fillable = [];
        foreach ($this->columns as $column) {
            if (!in_array($column->name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $fillable[] = "'$column->name'";
            }
        }
        if (empty($fillable))
            return '';

        $fillableString = implode(', ', $fillable);
        return "protected \$fillable = [$fillableString];";
    }

    /**
     * 获取字段的验证规则字符串
     * @param string|null $implodeStr
     * @param bool|null $isInsert
     * @return string
     */
    public function getValidateString(?string $implodeStr = "\n\t\t\t", ?bool $isInsert = false): string
    {
        $validateString = [];
        $skipColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];

        foreach ($this->columns as $column) {
            if (in_array($column->name, $skipColumns))
                continue;

            // 是否可空：1是否nullable，2是否有default
            $required = $column->nullable || $column->default ? 'nullable' : 'required';
            if ($isInsert) {
                $validateString[] = "'$column->name' => '', # $column->comment";
            } else {
                $validateString[] = "'$column->name' => '$required|$column->typeInValidate', # $column->comment";
            }
        }

        return implode($implodeStr, $validateString);
    }
}
