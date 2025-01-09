# Laravel Tools AI Package

这是一个 Laravel 工具包，提供了一系列相关的实用工具和功能。

## 1. 安装

```bash
composer require zuoge/laravel-tools-ai

php artisan vendor:publish --provider="Zuoge\LaravelToolsAi\ToolsAiServiceProvider"
```

## 2. 使用

该包会自动注册服务提供者。

## 3. 数据结构

### 3.1 生成 RouterCollection

- 通过扫描 app/Modules 下的所有 Controller 文件，生成 RouterCollection
- 对 action 的注解进行解析：
  - @title: 名称
  - @method: 请求方法: POST, GET 等
  - @withoutMiddleware: 排除的中间件，比如：json.response
  - @responseSchema: 响应返回的 schema，openapi 标准（可以通过把响应的 json 字符串，发给 AI 去生成）
- 对 request 参数进行解析：

```php
    /**
     * @title 管理员列表
     * @method GET
     * @withoutMiddleware json.response
     * @withoutMiddleware auth
     * @responseSchema {"type":"object","properties":{"success":{"type":"boolean","title":"请求是否成功","example":true},"data":{"type":"object","title":"返回的数据","properties":{"items":{"type":"array","title":"用户列表","items":{"type":"object","properties":{"id":{"type":"integer","title":"用户唯一标识","example":7}}}},"total":{"type":"integer","title":"总条目数","example":1},"page":{"type":"integer","title":"当前页码","example":1},"per_page":{"type":"integer","title":"每页条目数","example":20}}}}}
     */
    public function index()
    {
        $params = request()->validate([
            'username' => ['nullable', 'string'], # 用户名
            'name' => ['nullable', 'string'], # 姓名
            'email' => ['nullable', 'string'], # 邮箱
            'mobile' => ['nullable', 'string'], # 手机号
            'status' => ['nullable', 'string'], # 状态
            'page' => ['nullable', 'integer', 'min:1'], // 页码
            'per_page' => ['nullable', 'integer'], // 每页显示数量
        ]);
    }
```

### 3.2 生成 EnumCollection

- 通过扫描 app/Enums 下的所有 Enum 文件，生成 EnumCollection
- 每个 Enum 文件，都包括：文本、值、颜色

```php
<?php

declare(strict_types=1);

namespace App\Enums;

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

```

### 3.3 生成 DBTableCollection

- 通过扫描数据库中所有表结构，生成 DBTableCollection

## 4. 功能

### 4.1 全局错误处理

- 在任何代码中 throw 一个异常，都会被全局错误处理，返回一个标准的 json 响应。
- 响应结构中包含了一些调试信息，仅在 app.debug 为 true 时返回。
- 举例：

```php
/**
 * @title 测试error
 * @method GET
 * @throws \Exception
 * @return never
 */
public function testError()
{
    abort(500, 'test error');
}
```

- 响应：

```json
{
  "success": false,
  "status": 500,
  "errorMessage": "test error",
  "errorDetail": [],
  "debug": {...}
}
```

### 4.2 全局响应标准化

- 在所有控制器中，返回的响应结构都是标准的。比如：{ "data": [...], "success": "true" }
- 如果不想包裹 data 字段，则在控制器的方法上，增加 @withoutMiddleware json.response
- return 数组、collection、object 等对象类型。
- 如果控制器方法为 void，则返回 { "success": "true" }

### 4.3 自动生成 api routes

- 通过 RouterCollection，计算出 router
- 想要改变 router 内容，可以直接修改 action 的注解

### 4.4 自动生成 openapi v3 json 文件

- 通过 RouterCollection，生成 openapi v3 json 文件
- 通过 http://localhost:8000/api/docs/openapi 访问
- 在 app.debug = false 的时候，禁用此功能
- 扩展 x-enum: 用于描述 enum 的信息，前端根据这个可以自动生成代码
- 扩展 x-table: 用于描述 table 的信息，前端根据这个可以自动生成文档

### 4.5 常用 Traits 封装

- BaseControllerTrait.php
- BaseEnumTrait.php
- BaseModelTrait.php

### 4.6 常用 Builder::macro

- whenWhereLike
- whenWhere
