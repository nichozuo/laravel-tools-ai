<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Models;

use Illuminate\Support\Collection;

/**
 * 控制器模型类
 */
class ControllerModel
{
    /**
     * 控制器类名
     * 通过::class获取
     * 比如：App\Modules\Admin\Controllers\AdminController
     */
    public string $controllerClass = '';

    /**
     * 控制器标题
     * 通过解析控制器类的注解中的@title
     * 比如：@title 管理员管理
     */
    public string $title = '';

    /**
     * 模块名称
     * 通过解析控制器类名获取：
     * - 去掉App\Modules\
     * - 去掉\Controllers\*
     * - 用\进行分割成数组
     * 比如：['Admin']、['Customer']、['Admin','Wechat']
     * @var array<string>
     */
    public array $modules = [];

    /**
     * 控制器名称
     * 通过解析控制器类名获取：
     * - 取split('\')的最后一位
     * - 去掉Controller获得
     * 比如：Admin
     */
    public string $controller = '';

    /**
     * 中间件组名称
     * 通过$modules数组获取：
     * - 取数组的第一个位 和 'Group' 组成的字符串
     * 比如：AdminGroup
     */
    public string $middlewareGroupName = '';

    /**
     * 控制器中的方法集合
     * @var Collection<ControllerActionModel>
     */
    public Collection $actions;

    public function __construct()
    {
        $this->actions = collect();
    }
}
