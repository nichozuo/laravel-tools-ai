<?php

namespace Zuoge\LaravelToolsAi\Traits;

use ReflectionClass;

trait BaseEnumTrait
{
    /**
     * 获取所有枚举值
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_column(static::cases(), 'value');
    }

    /**
     * 获取状态和描述的组合字符串
     *
     * @param string $prefix 注释前缀
     */
    public static function comment(string $prefix): string
    {
        $comments = [];
        foreach (static::cases() as $case) {
            $comments[] = $case->value . ':' . $case->text()[$case->value];
        }
        return $prefix . ': ' . implode(',', $comments);
    }

    /**
     * 生成 TypeScript 枚举代码
     *
     * @return string
     */
    public static function toTsCode(): string
    {
        $enumName = (new ReflectionClass(self::class))->getShortName();

        $items = [];
        foreach (static::cases() as $case) {
            $items[] = sprintf(
                "  '%s': {\"text\":\"%s\",\"color\":\"%s\",\"value\":\"%s\"}", 
                $case->value,
                self::text()[$case->value],
                self::colors()[$case->value],
                $case->value
            );
        }

        return sprintf(
            "export const %s = {\n%s\n};",
            $enumName,
            implode(",\n", $items)
        );
    }
} 