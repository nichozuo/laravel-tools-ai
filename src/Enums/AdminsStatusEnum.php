<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Enums;

use Zuoge\LaravelToolsAi\Traits\BaseEnumTrait;

/**
 * @intro 管理员状态
 */
enum AdminsStatusEnum: string
{
    use BaseEnumTrait;

    case ENABLED = 'ENABLED';
    case DISABLED = 'DISABLED';

    public static function text(): array
    {
        return [
            self::ENABLED->value => '启用',
            self::DISABLED->value => '禁用',
        ];
    }

    public static function colors(): array
    {
        return [
            self::ENABLED->value => '#008000', // 绿色
            self::DISABLED->value => '#808080', // 灰色
        ];
    }
}