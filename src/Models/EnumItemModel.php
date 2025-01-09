<?php

namespace Zuoge\LaravelToolsAi\Models;

/**
 * 枚举项模型类
 * 用于表示枚举中的单个选项
 */
class EnumItemModel
{
    /**
     * 枚举项的显示文本
     * @var string
     */
    public string $text = '';

    /**
     * 枚举项的值
     * @var string
     */
    public string $value = '';

    /**
     * 枚举项的颜色（用于前端显示）
     * @var string
     */
    public string $color = '';
}
