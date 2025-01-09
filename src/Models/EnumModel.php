<?php

namespace Zuoge\LaravelToolsAi\Models;

/**
 * 枚举模型类
 * 用于定义一个完整的枚举类型
 */
class EnumModel
{
    /**
     * 枚举的名称
     * @var string
     */
    public string $name = '';

    /**
     * 枚举的标题/显示名称
     * @var string
     */
    public string $title = '';

    /**
     * 枚举项列表
     * @var EnumItemModel[]
     */
    public array $items = [];
}
