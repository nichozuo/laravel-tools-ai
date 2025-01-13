<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use Zuoge\LaravelToolsAi\Exceptions\Handler;
use Zuoge\LaravelToolsAi\Http\Middleware\JsonResponseMiddleware;
use Zuoge\LaravelToolsAi\Commands\GenFiles;
use Zuoge\LaravelToolsAi\Commands\Dump;
use Zuoge\LaravelToolsAi\Macros\BuilderMacros;
use Throwable;

class ToolsAiServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册异常处理器
        $this->app->singleton(
            ExceptionHandler::class,
            Handler::class
        );
    }

    /**
     * 启动服务
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        // register helpers
        require_once(__DIR__ . '/helpers.php');

        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/common.php' => config_path('common.php'),
        ], 'common');

        // 仅在调试模式下加载测试路由
        if ($this->app['config']->get('app.debug')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }

        // 配置异常处理
        // $this->bootExceptionHandling();

        // 配置应用的中间件
        $this->bootMiddlewareHandling();

        // 配置QueryBuilder
        BuilderMacros::boot();

        // 注册命令
        $this->commands([
            GenFiles\GenMigrationFileCommand::class,
            GenFiles\GenModelFileCommand::class,
            GenFiles\GenAllModelFileCommand::class,
            GenFiles\GenControllerFileCommand::class,

            Dump\DumpTableCommand::class,
        ]);
    }

    /**
     * 配置应用的异常处理
     * @throws BindingResolutionException
     */
    protected function bootExceptionHandling(): void
    {
        /** @var Handler $handler */
        $handler = $this->app->make(ExceptionHandler::class);

        // 注册渲染器
        $handler->renderable(function (Throwable $e) use ($handler) {
            return $handler->render(request(), $e);
        });

        // 注册报告器
        $handler->reportable(function () {
            return false;
        });
    }

    /**
     * 配置应用的中间件
     * @throws BindingResolutionException
     */
    protected function bootMiddlewareHandling(): void
    {
        // 获取路由器实例
        $router = $this->app->make('router');

        // 注册中间件别名
        $router->aliasMiddleware('json.response', JsonResponseMiddleware::class);

        // 将中间件添加到 API 组
        // $router->pushMiddlewareToGroup('api', 'json.response');
    }
}