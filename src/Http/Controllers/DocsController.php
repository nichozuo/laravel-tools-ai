<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Zuoge\LaravelToolsAi\Collections\EnumCollection;
use Zuoge\LaravelToolsAi\Collections\RouterCollection;
use Zuoge\LaravelToolsAi\Collections\DBTableCollection;
use Zuoge\LaravelToolsAi\Models\ControllerModel;
use Zuoge\LaravelToolsAi\Models\ControllerActionModel;
use ReflectionException;

/**
 * 生成openapi的文档
 * - 根据ModuleRouteService中解析出来的 RouterModel[] 对象数组
 * - 按照 @https://spec.openapis.org/oas/v3.1.1.html 定义的openapi v3.1.1 的定义
 * - 在DocsController的控制器中，生成openapi的json对象，并通过json response返回。
 * - 生成内容规则
 *  - tags: 根据 RouterModel中所有的 [...modules, controller] 生成，用/分割
 *  - paths:
 *      - /{path} 通过 RouterModel->uri 生成
 *      - operation 通过 RouterModel->method 生成
 *          - summary，取 RouterModel->title
 *          - tags 通过 [...RouterModel->modules, RouterModel->controller] 生成，用/分割
 *          - requestBody 通过 RouterModel->requestBody 生成
 *          - parameters 不要生成
 *          - security 不要生成
 *          - response 不要生成
 */
class DocsController extends Controller
{

    /**
     * @title 获取API文档
     * @description 生成OpenAPI 3.1.1规范的API文档
     * @withoutMiddleware json.response
     * @method GET
     * @throws ReflectionException
     */
    public function openapi(): JsonResponse
    {
        // 获取所有数据库信息
        $routes = RouterCollection::getInstance()->getCollection();
        $enums = EnumCollection::getInstance()->getCollection();
        $db = DBTableCollection::getInstance()->getCollection()->tables;


        // 构建OpenAPI基础信息
        $openapi = [
            'openapi' => '3.1.1',
            'info' => [
                'title' => config('app.name') . ' API Documentation',
                'version' => '1.0.0',
            ],
            'servers' => [
                [
                    'url' => config('app.url') . '/api',
                    'description' => '接口服务器'
                ]
            ],
            'tags' => $this->buildTags($routes),
            'paths' => $this->buildPaths($routes),
            'x-enum' => $this->buildEnum($enums),
            'x-table' => $this->buildTable($db),
        ];

        return response()->json($openapi);
    }

    /**
     * 构建tags
     * @param Collection $routes
     * @return array
     */
    protected function buildTags(Collection $routes): array
    {
        $uniqueTags = [];
        $routes->each(function (ControllerModel $controller) use (&$uniqueTags) {
            $tag = implode('/', array_merge($controller->modules, [$controller->controller . 'Controller']));
            $uniqueTags[$tag] = [
                'name' => $tag,
                'description' => $controller->title
            ];
        });
        return array_values($uniqueTags);
    }

    /**
     * 构建paths
     * @param Collection $routes
     * @return array
     */
    protected function buildPaths(Collection $routes): array
    {
        $paths = [];
        $routes->each(function (ControllerModel $controller) use (&$paths) {
            $controller->actions->each(function (ControllerActionModel $action) use (&$paths, $controller) {
                $path = '/' . $action->uri;
                $method = strtolower($action->method);

                // 生成该路由的tag
                $routeTag = implode('/', array_merge($controller->modules, [$controller->controller . 'Controller']));

                // 构建路径对象
                $pathItem = [
                    'summary' => $action->action,
                    'description' => $action->title,
                    'tags' => [$routeTag],
                ];

                if (!empty($action->description)) {
                    $pathItem['description'] = $action->description;
                }

                // 只有在有requestBody时才添加
                if (!empty($action->requestBody['properties'])) {
                    $pathItem['requestBody'] = [
                        'content' => [
                            'application/x-www-form-urlencoded' => [
                                'schema' => $action->requestBody
                            ]
                        ]
                    ];
                }

                // 只有在有responseSchema时才添加
                if ($action->responseSchema !== '') {
                    $pathItem['responses'] = [
                        '200' => [
                            'description' => '成功',
                            'content' => [
                                'application/json' => [
                                    'schema' => json_decode($action->responseSchema, true)
                                ]
                            ]
                        ]
                    ];
                }

                $paths[$path][$method] = $pathItem;
            });
        });

        return $paths;
    }

    /**
     * 构建enum
     * @param Collection $enums
     * @return array
     */
    protected function buildEnum(Collection $enums): array
    {
        return $enums->toArray();
    }

    /**
     * 构建用于Markdown渲染的表格数据
     * @param Collection $db
     * @return array
     */
    protected function buildTable(Collection $db): array
    {
        return $db->toArray();
    }
}