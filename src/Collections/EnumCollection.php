<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Collections;

use Exception;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionException;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Zuoge\LaravelToolsAi\Models\EnumModel;
use Zuoge\LaravelToolsAi\Models\EnumItemModel;
use phpDocumentor\Reflection\DocBlockFactory;

/**
 * 枚举集合类
 * 
 * 用于收集和管理枚举信息，主要功能：
 * 1. 扫描指定目录下的枚举文件
 * 2. 解析枚举类的信息，包括类注解和枚举项
 * 3. 生成标准化的枚举模型对象
 * 
 * 使用单例模式确保全局只有一个实例，减少内存占用
 * 使用懒加载模式，只在首次获取数据时初始化集合
 */
class EnumCollection
{
    /**
     * 存储唯一实例
     * @var self|null
     */
    protected static ?self $instance = null;

    /**
     * 内存中的集合数据
     * @var Collection|null
     */
    protected ?Collection $collection = null;

    /**
     * DocBlock解析器实例
     * @var DocBlockFactory
     */
    protected DocBlockFactory $docBlockFactory;

    /**
     * 构造函数
     * 初始化 DocBlock 解析器
     */
    protected function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    /**
     * 获取单例实例
     * 确保全局只有一个 EnumCollection 实例
     */
    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * 获取枚举集合数据
     * 使用懒加载模式，仅在首次调用时初始化数据
     * 
     * @throws ReflectionException
     * @return Collection 返回枚举模型集合
     */
    public function getCollection(): Collection
    {
        if ($this->collection === null) {
            $this->collection = $this->init();
        }
        return $this->collection;
    }

    /**
     * 初始化枚举集合
     * 扫描应用的 Enums 目录，收集所有枚举类信息
     * 
     * @throws ReflectionException
     * @return Collection 返回枚举模型集合
     */
    protected function init(): Collection
    {
        $enumPath = app_path('Enums');
        if (!file_exists($enumPath)) {
            return collect();
        }
        return $this->scanEnums($enumPath);
    }

    /**
     * 扫描枚举文件
     * 使用 Symfony Finder 组件扫描目录下的所有 PHP 文件
     * 
     * @param string $enumPath 枚举文件目录路径
     * @throws ReflectionException
     * @return Collection 返回枚举模型集合
     */
    protected function scanEnums(string $enumPath): Collection
    {
        $collection = collect();
        $finder = new Finder();
        $finder->files()
            ->in($enumPath)
            ->name('*.php');

        foreach ($finder as $file) {
            if ($enumClass = $this->getEnumClass($file)) {
                $collection->push($this->parseEnumClass($enumClass));
            }
        }

        return $collection;
    }

    /**
     * 解析枚举类
     * 将枚举类转换为标准的 EnumModel 对象
     * 
     * @param string $enumClass 枚举类的完整类名
     * @throws ReflectionException
     * @return EnumModel 返回枚举模型对象
     */
    protected function parseEnumClass(string $enumClass): EnumModel
    {
        // 如果类不存在或不是枚举类，返回空模型
        if (!class_exists($enumClass) || !method_exists($enumClass, 'cases')) {
            return new EnumModel();
        }

        $reflection = new ReflectionClass($enumClass);
        $cases = $enumClass::cases();

        // 获取枚举类的文本和颜色映射
        $text = $enumClass::text();
        $colors = $enumClass::colors();

        // 创建并填充枚举模型
        $enumModel = new EnumModel();
        $enumModel->name = class_basename($enumClass);
        $enumModel->title = $this->getEnumDescription($reflection);
        $enumModel->items = [];

        // 处理每个枚举项
        foreach ($cases as $case) {
            $item = new EnumItemModel();
            $item->text = $text[$case->value] ?? '';
            $item->value = $case->value;
            $item->color = $colors[$case->value] ?? '';
            $enumModel->items[] = $item;
        }

        return $enumModel;
    }

    /**
     * 获取枚举类的描述信息
     * 从类的 DocBlock 中解析 @intro 标签内容
     * 
     * @param ReflectionClass $reflection 反射类实例
     * @return string 返回枚举描述，如果没有则返回空字符串
     */
    protected function getEnumDescription(ReflectionClass $reflection): string
    {
        $docComment = $reflection->getDocComment();
        if (!$docComment) {
            return '';
        }

        try {
            $docBlock = $this->docBlockFactory->create($docComment);
            $tags = $docBlock->getTagsByName('intro');
            if (!empty($tags)) {
                return (string) $tags[0];
            }
        } catch (Exception $e) {
            dump($e->getMessage());
            return '';
        }

        return '';
    }

    /**
     * 获取枚举类的完整类名
     * 根据文件路径构建完整的类名
     * 
     * @param SplFileInfo $file 文件信息对象
     * @return string|null 返回类名，如果类不存在则返回 null
     */
    protected function getEnumClass(SplFileInfo $file): ?string
    {
        $relativePath = substr($file->getRealPath(), strlen(app_path()) + 1);
        $class = 'App\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

        return class_exists($class) ? $class : null;
    }
}
