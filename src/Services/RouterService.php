<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Zuoge\LaravelToolsAi\Collections\RouterCollection;
use Zuoge\LaravelToolsAi\Models\ControllerModel;
use Zuoge\LaravelToolsAi\Models\ControllerActionModel;

/**
 * 模块路由服务
 *
 * 负责将路由信息注册到系统中，主要功能：
 * 1. 从RouterCollection获取路由信息
 * 2. 将路由信息注册到Laravel路由系统中
 */
class RouterService
{
    /**
     * 注册所有模块的路由
     */
    public static function AutoRegister(): void
    {
        // 获取路由集合
        $collection = RouterCollection::getInstance()->getCollection();

        // 注册每个控制器的路由
        $collection->each(function (ControllerModel $controllerModel) {
            // 注册控制器中的每个方法路由
            $controllerModel->actions->each(function (ControllerActionModel $actionModel) use ($controllerModel) {
                self::registerRoute($controllerModel, $actionModel);
            });
        });
    }

    /**
     * 注册单个路由
     */
    private static function registerRoute(ControllerModel $controllerModel, ControllerActionModel $actionModel): void
    {
        $route = Route::match(
            [$actionModel->method],
            $actionModel->uri,
            [$controllerModel->controllerClass, $actionModel->action]
        )
            ->name(self::generateRouteName($controllerModel, $actionModel))
            ->middleware($controllerModel->middlewareGroupName);

        // 应用排除的中间件
        if (!empty($actionModel->withoutMiddlewares)) {
            $route->withoutMiddleware($actionModel->withoutMiddlewares);
        }
    }

    /**
     * 生成路由名称
     */
    private static function generateRouteName(ControllerModel $controllerModel, ControllerActionModel $actionModel): string
    {
        return implode('.', [
            ...array_map(fn($module) => Str::snake($module), $controllerModel->modules),
            Str::snake($controllerModel->controller),
            Str::snake($actionModel->action)
        ]);
    }
}
