<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Models;

/**
 * 控制器方法模型类
 */
class ControllerActionModel
{
    /**
     * 控制器方法名
     * 通过反射获取
     * 比如：uploadFile
     */
    public string $action = '';

    /**
     * 路由URI
     * 通过 [...modules, controller, action] 生成
     * - 先合并成一个数组
     * - 再通过/连接成一个字符串
     * - 最后用小写蛇形形式
     * 比如：admin/admin/upload_file
     */
    public string $uri = '';

    /**
     * HTTP请求方法
     * 通过 phpdocumentor/reflection-docblock 包读取和解析方法的注释内容，以@开头的
     * - 通过@method注释获取
     * - 如果没有，则默认POST
     * 比如：POST
     */
    public string $method = 'POST';

    /**
     * 路由名称
     * 通过 phpdocumentor/reflection-docblock 包读取和解析方法的注释内容，以@开头的
     * - 通过@title注释获取
     * - 如果没有，则默认取action的值
     * 比如：列表
     */
    public string $title = '';

    /**
     * 路由名称
     * 通过 phpdocumentor/reflection-docblock 包读取和解析方法的注释内容，以@开头的
     * - 通过@description注释获取
     * - 如果没有，则默认空
     * 比如：查询管理员的列表的说明
     */
    public string $description = '';

    /**
     * 排除的中间件列表
     * 通过 phpdocumentor/reflection-docblock 包读取和解析方法的注释内容，以@开头的
     * - 通过@withoutMiddleware获取
     * - 注解中可以存在多个
     * 比如：['auth:api', 'json.response']
     * @var array<string>
     */
    public array $withoutMiddlewares = [];

    /**
     * 路由请求参数
     * 从方法体的代码里面的 $params = request()->validate([...]) 获取
     * 按照openapiV3的schema object的定义的结构，解析成对应的对象数组
     * @var array<string, array<string, mixed>>
     */
    public array $requestBody = [
        'type' => 'object',
        'properties' => [],
        'required' => []
    ];

    /**
     * 返回数据结构
     * 通过 phpdocumentor/reflection-docblock 包读取和解析方法的注释内容，以@开头的
     * - 通过@responseSchema获取
     * - 如果没有，则默认空
     */
    public string $responseSchema = '';
}
