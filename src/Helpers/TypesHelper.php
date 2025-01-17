<?php

namespace Zuoge\LaravelToolsAi\Helpers;

/**
 * 类型帮助类
 *
 * 提供将MySQL数据类型映射到PHP属性和验证规则的方法。
 */
class TypesHelper
{
    /**
     * 将MySQL列类型映射到PHP属性类型
     *
     * @param array $column 包含列信息的数组
     * @return string 返回PHP属性类型
     */
    public static function mapMysqlTypeToPhpProperty(array $column): string
    {
        // 判断列是否可为空
        $nullable = $column['nullable'] ? '?' : '';

        // 特殊处理tinyint(1)类型，映射为boolean
        if ($column['type'] === 'tinyint(1)') {
            $type = "boolean";
        } else {
            // 使用match表达式根据类型名称映射到PHP类型
            $type = match (strtolower($column['type_name'])) {
                'bigint', 'int', 'smallint', 'mediumint' => 'integer',
                'decimal', 'float', 'double' => 'float',
                'date', 'datetime', 'timestamp' => 'Carbon', // 使用Carbon类处理日期时间
                'json' => 'array',
                default => 'string', // 默认映射为string
            };
        }
        return $nullable . $type;
    }

    /**
     * 将MySQL列类型映射到PHP验证规则
     *
     * @param array $column 包含列信息的数组
     * @return string 返回PHP验证规则
     */
    public static function mapMysqlTypeToPhpValidate(array $column): string
    {
        if ($column['type'] === 'tinyint(1)')
            return "boolean";

        // 使用match表达式根据类型名称映射到验证规则
        return match (strtolower($column['type_name'])) {
            'bigint', 'int', 'smallint', 'mediumint' => 'integer',
            'decimal', 'float', 'double' => 'numeric',
            'date', 'datetime', 'timestamp' => 'date',
            'json' => 'array',
            default => 'string', // 默认映射为string
        };
    }

    /**
     * 将PHP验证类型映射到TypeScript类型
     *
     * @param array $rules
     * @return string 返回TypeScript类型
     */
    public static function mapPhpValidateTypeToTsType(array $rules): string
    {
        // 使用match表达式根据PHP验证类型映射到TypeScript类型
        return match (strtolower($rules[1])) {
            // 这里可以添加更多的映射规则
            'integer', 'numeric' => 'number',
            'array' => 'any[]',
            'boolean' => 'boolean',
            'date' => 'Date',
            default => 'string', // 默认映射为string
        };
    }
}
