<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Collections;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;
use Zuoge\LaravelToolsAi\Helpers\TypesHelper;
use Zuoge\LaravelToolsAi\Models\ControllerModel;
use Zuoge\LaravelToolsAi\Models\ControllerActionModel;

/**
 * 路由集合类
 *
 * 用于收集和管理路由信息，主要功能：
 * 1. 扫描指定目录下的控制器文件
 * 2. 解析控制器方法的注解信息
 * 3. 生成控制器和方法模型对象
 */
class RouterCollection
{
    /**
     * 存储唯一实例
     */
    protected static ?self $instance = null;

    /**
     * 内存中的集合数据
     * @var Collection<ControllerModel>|null
     */
    protected ?Collection $collection = null;

    private DocBlockFactory $docBlockFactory;

    protected function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    /**
     * 获取单例实例
     */
    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * 获取集合数据
     * @throws ReflectionException
     * @return Collection<ControllerModel>
     */
    public function getCollection(): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->init();
        }
        return $this->collection;
    }

    /**
     * @param string $controllerClass
     * @return ControllerModel|null
     * @throws ReflectionException
     * @throws Exception
     */
    public function getByControllerClass(string $controllerClass): ?ControllerModel
    {
        $route = $this->getCollection()->where('controllerClass', $controllerClass)->first();
        if (!$route)
            ee("Route $controllerClass not found!");
        return $route;
    }

    /**
     * 初始化路由集合
     * @return Collection<ControllerModel>
     * @throws ReflectionException
     */
    protected function init(): Collection
    {
        $modulesPath = app_path('Modules');
        if (!file_exists($modulesPath)) {
            return collect();
        }
        return $this->scanControllers($modulesPath);
    }

    /**
     * 扫描控制器文件
     * @throws ReflectionException
     * @return Collection<ControllerModel>
     */
    protected function scanControllers(string $modulesPath): Collection
    {
        $collection = collect();
        $finder = new Finder();
        $finder->files()
            ->in($modulesPath)
            ->name('*Controller.php')
            ->notName('BaseController.php');

        foreach ($finder as $file) {
            if ($controllerClass = $this->getControllerClass($file)) {
                $collection->push($this->parseController($controllerClass));
            }
        }

        return $collection;
    }

    /**
     * 解析控制器信息
     * @throws ReflectionException
     */
    protected function parseController(string $controllerClass): ControllerModel
    {
        $reflection = new ReflectionClass($controllerClass);
        $modules = $this->getModulesFromClass($controllerClass);
        $controller = str_replace('Controller', '', $reflection->getShortName());

        $controllerModel = new ControllerModel();
        $controllerModel->controllerClass = $controllerClass;
        $controllerModel->modules = $modules;
        $controllerModel->controller = $controller;
        $controllerModel->middlewareGroupName = Str::studly($modules[0]) . 'Group';
        $controllerModel->title = $this->getControllerTitle($reflection);

        // 解析控制器中的所有方法
        collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(
                fn(ReflectionMethod $method) =>
                $method->class === $controllerClass && $method->getName() !== '__construct'
            )
            ->each(function (ReflectionMethod $method) use ($controllerModel, $modules) {
                if ($actionModel = $this->parseMethodRoute($method, $modules, $controllerModel->controller)) {
                    $controllerModel->actions->push($actionModel);
                }
            });

        return $controllerModel;
    }

    /**
     * 从类名中获取模块信息
     * @return array<string>
     */
    protected function getModulesFromClass(string $controllerClass): array
    {
        return array_slice(explode('\\', str_replace(
            ['App\\Modules\\', 'Controller'],
            ['', ''],
            $controllerClass
        )), 0, -1);
    }

    /**
     * 解析方法的路由信息
     */
    protected function parseMethodRoute(
        ReflectionMethod $method,
        array $modules,
        string $controller
    ): ?ControllerActionModel {
        if (!$docComment = $method->getDocComment()) {
            return null;
        }

        $actionModel = new ControllerActionModel();
        $docBlock = $this->docBlockFactory->create($docComment);

        $actionModel->action = $method->getName();

        // 生成URI
        $parts = array_map(
            fn($part) => Str::snake($part),
            array_merge($modules, [$controller, $method->getName()])
        );
        $actionModel->uri = implode('/', $parts);

        // 填充文档信息
        $this->fillDocInfo($actionModel, $docBlock);

        // 解析请求参数
        $actionModel->requestBody = $this->parseMethodRequestBody($method);

        return $actionModel;
    }

    /**
     * 填充文档信息
     */
    protected function fillDocInfo(ControllerActionModel $actionModel, $docBlock): void
    {
        // 解析HTTP方法
        $methodTag = $docBlock->getTagsByName('@method');
        $actionModel->method = !empty($methodTag) ? strtoupper((string) $methodTag[0]) : 'POST';

        // 解析标题
        $titleTag = $docBlock->getTagsByName('title');
        $actionModel->title = !empty($titleTag) ? (string) $titleTag[0] : $actionModel->action;

        // 解析描述
        $descriptionTag = $docBlock->getTagsByName('description');
        $actionModel->description = !empty($descriptionTag) ? (string) $descriptionTag[0] : '';

        // 解析响应schema
        $responseSchemaTag = $docBlock->getTagsByName('responseSchema');
        $actionModel->responseSchema = !empty($responseSchemaTag) ? (string) $responseSchemaTag[0] : '';

        // 解析要排除的中间件
        $withoutMiddlewareTags = $docBlock->getTagsByName('withoutMiddleware');
        $actionModel->withoutMiddlewares = array_map(
            fn($tag) => (string) $tag,
            $withoutMiddlewareTags
        );
    }

    /**
     * 解析方法中的参数验证规则
     * @return array<string, array<string, mixed>>
     */
    protected function parseMethodRequestBody(ReflectionMethod $method): array
    {
        $methodContent = $this->getMethodContent($method);
        $validationRules = $this->extractValidationRules($methodContent);
        if (!$validationRules) {
            return [];
        }
        // 去掉前面被注释的行
        $validationRules = preg_replace('/^\s*\/\/.*$/m', '', $validationRules);
        return $this->parseValidationRules($validationRules);
    }

    /**
     * 获取方法内容
     */
    protected function getMethodContent(ReflectionMethod $method): string
    {
        $content = array_map(
            fn($line) => trim($line),
            array_slice(
                file($method->getFileName()),
                $method->getStartLine(),
                $method->getEndLine() - $method->getStartLine()
            )
        );
        return implode("\n", $content);
    }

    /**
     * 提取验证规则
     */
    protected function extractValidationRules(string $content): ?string
    {
        if (preg_match('/\$params\s*=\s*request\(\)->validate\(\[\s*(.*?)\s*]\);/s', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * 解析验证规则
     * @return array<string, array<string, mixed>>
     */
    protected function parseValidationRules(string $validationRules): array
    {
        $requestBody = [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ];

        // 按行分割验证规则
        $lines = explode("\n", $validationRules);

        foreach ($lines as $line) {
            // 去掉前面被注释的行
            $line = preg_replace('/^\s*\/\/.*$/m', '', $line);
            if (empty(trim($line))) {
                continue;
            }

            preg_match(
                "/'([^']+)'\s*=>\s*'([^']+)'(?:\s*,\s*(?:#|\/\/)\s*(.+))?/m",
                $line,
                $match
            );

            if ($match) {
                $fieldName = $match[1];
                $rules = array_map('trim', explode('|', $match[2]));
                $comment = $match[3] ?? '';
                $property = [
                    'type' => TypesHelper::mapPhpValidateTypeToTsType($rules),
                    'description' => trim($comment),
                    'required' => in_array('required', $rules)
                ];

                if ($property['required']) {
                    $requestBody['required'][] = $fieldName;
                }

                $requestBody['properties'][$fieldName] = $property;
            }
        }

        // 如果required为空，则删除
        if (!count($requestBody['required'])) {
            unset($requestBody['required']);
        }

        return $requestBody;
    }

    /**
     * 获取控制器的完整类名
     */
    protected function getControllerClass($file): ?string
    {
        $relativePath = substr($file->getRealPath(), strlen(app_path()) + 1);
        $class = 'App\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

        return class_exists($class) ? $class : null;
    }

    /**
     * 获取控制器的标题
     */
    protected function getControllerTitle(ReflectionClass $reflection): string
    {
        $docComment = $reflection->getDocComment();
        if (!$docComment) {
            return $reflection->getShortName();
        }

        $docBlock = $this->docBlockFactory->create($docComment);
        $titleTag = $docBlock->getTagsByName('title');

        return !empty($titleTag) ? (string) $titleTag[0] : $reflection->getShortName();
    }
}
