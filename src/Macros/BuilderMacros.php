<?php

namespace Zuoge\LaravelToolsAi\Macros;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class BuilderMacros
{
    /**
     * 注册所有的查询构建器宏
     * @return void
     */
    public static function boot(): void
    {
        // 注册 Query Builder 宏
        static::registerQueryBuilderMacros();

        // 注册 Eloquent Builder 宏
        static::registerEloquentBuilderMacros();
    }

    /**
     * 注册 Query Builder 相关的宏
     */
    private static function registerQueryBuilderMacros(): void
    {
        QueryBuilder::macro('ifWhere', static::ifWhere());
        QueryBuilder::macro('ifWhereLike', static::ifWhereLike());
        QueryBuilder::macro('ifWhereLikeKeyword', static::ifWhereLikeKeyword());
        QueryBuilder::macro('ifWhereNumberRange', static::ifWhereNumberRange());
        QueryBuilder::macro('ifWhereDateRange', static::ifWhereDateRange());
        QueryBuilder::macro('order', static::order());
        QueryBuilder::macro('unique', static::unique());
        QueryBuilder::macro('ifIsNull', static::ifIsNull());
        QueryBuilder::macro('ifIsNotNull', static::ifIsNotNull());
    }

    /**
     * 注册 Eloquent Builder 相关的宏
     */
    private static function registerEloquentBuilderMacros(): void
    {
        EloquentBuilder::macro('forSelect', static::forSelect());
        EloquentBuilder::macro('page', static::page());
        EloquentBuilder::macro('getById', static::getById());
        EloquentBuilder::macro('ifHasWhereLike', static::ifHasWhereLike());
    }

    /**
     * 检查参数是否有效
     * @param array $params 参数数组
     * @param string $key 键名
     * @return bool
     */
    public static function isValidParam(array $params, string $key): bool
    {
        return array_key_exists($key, $params) && $params[$key] !== null && $params[$key] !== '';
    }

    /**
     * 条件where查询
     * @return \Closure
     */
    private static function ifWhere(): \Closure
    {
        return function (array $params, string $key, ?string $field = null) {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;

            return $query->when(
                BuilderMacros::isValidParam($params, $key),
                fn($q) => $q->where($field ?? $key, $params[$key])
            );
        };
    }

    /**
     * 模糊查询
     * @return \Closure
     */
    private static function ifWhereLike(): \Closure
    {
        return function (array $params, string $key, ?string $field = null) {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;

            return $query->when(
                BuilderMacros::isValidParam($params, $key),
                fn($q) => $q->where($field ?? $key, 'like', "%{$params[$key]}%")
            );
        };
    }

    /**
     * 多字段关键词模糊查询
     * @return \Closure
     */
    private static function ifWhereLikeKeyword(): \Closure
    {
        return function (array $params, string $key, array $fields) {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;
            return $query->when(
                BuilderMacros::isValidParam($params, $key),
                fn() => $query->where(function ($q) use ($params, $key, $fields) {
                    foreach ($fields as $field) {
                        $q->orWhere($field, 'like', "%{$params[$key]}%");
                    }
                })
            );
        };
    }

    /**
     * 数字范围查询
     * @return \Closure
     */
    private static function ifWhereNumberRange(): \Closure
    {
        return function (array $params, string $key, ?string $field = null) {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;
            if (!isset($params[$key])) {
                return $query;
            }

            $dataRange = $params[$key];
            if (count($dataRange) != 2) {
                ee("{$key}参数必须是两个值");
            }

            $start = $dataRange[0] ?? null;
            $end = $dataRange[1] ?? null;
            $field = $field ?? $key;

            if ($start && !$end) {
                return $query->where($field, '>=', $start);
            }
            if (!$start && $end) {
                return $query->where($field, '<=', $end);
            }
            return $query->whereBetween($field, [$start, $end]);
        };
    }

    /**
     * 日期范围查询
     * @return \Closure
     */
    private static function ifWhereDateRange(): \Closure
    {
        return function (array $params, string $key, ?string $field = null, ?string $type = 'datetime') {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;
            if (!isset($params[$key]) || empty($params[$key])) {
                return $query;
            }

            $range = $params[$key];
            if (count($range) != 2) {
                ee("{$key}参数必须是两个值");
            }

            $start = $range[0] == '' || $range[0] == null ? null : Carbon::parse($range[0]);
            $end = $range[1] == '' || $range[1] == null ? null : Carbon::parse($range[1]);

            $start = $start ? ($type == 'date' ? $start->toDateString() : $start->startOfDay()->toDateTimeString()) : null;
            $end = $end ? ($type == 'date' ? $end->toDateString() : $end->endOfDay()->toDateTimeString()) : null;

            $field = $field ?? $key;

            if ($start && !$end) {
                return $query->where($field, '>=', $start);
            }
            if (!$start && $end) {
                return $query->where($field, '<=', $end);
            }
            return $query->whereBetween($field, [$start, $end]);
        };
    }

    /**
     * 关联模型的模糊查询
     * @return \Closure
     */
    private static function ifHasWhereLike(): \Closure
    {
        return function (array $params, string $key, string $relation, ?string $field = null) {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;
            return $query->when(
                array_key_exists($key, $params) && $params[$key] !== '',
                function (QueryBuilder|EloquentBuilder $q) use ($params, $key, $relation, $field) {
                    return $q->whereHas($relation, function ($q1) use ($params, $key, $field) {
                        return $q1->where($field ?? $key, 'like', "%{$params[$key]}%");
                    });
                }
            );
        };
    }

    /**
     * 排序处理
     * @return \Closure
     */
    private static function order(): \Closure
    {
        return function (?string $key = 'sorter', ?string $defaultField = 'id') {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;
            $params = request()->validate([$key => 'nullable|array']);
            if ($params[$key] ?? false) {
                $orderBy = $params[$key];
                if (count($orderBy) == 2) {
                    $field = $orderBy[0];
                    $sort = $orderBy[1] == 'descend' ? 'desc' : 'asc';
                    return $query->orderBy($field, $sort);
                }
            }
            return $query->orderByDesc($defaultField);
        };
    }

    /**
     * 分页处理
     * @return \Closure
     */
    private static function page(): \Closure
    {
        return function () {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;
            $perPage = request()->validate(['perPage' => 'nullable|integer'])['perPage'] ?? 10;
            $allow = config('project.perPageAllow', [10, 20, 50, 100]);
            if (!in_array($perPage, $allow)) {
                ee('分页参数错误');
            }
            return $query->paginate($perPage);
        };
    }

    /**
     * 下拉选择数据
     * @return \Closure
     */
    private static function forSelect(): \Closure
    {
        return function (?string $key1 = 'id', ?string $key2 = 'name', ?string $orderByDesc = 'id') {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;
            return $query->selectRaw("$key1, $key2")->orderByDesc($orderByDesc)->get();
        };
    }

    /**
     * 唯一性检查
     * @return \Closure
     */
    private static function unique(): \Closure
    {
        return function (array $params, array $keys, ?string $label = null, string $field = 'id', ?int $keyIndex = 0) {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;
            $model = $query->where(Arr::only($params, $keys))->first();
            if ($model && $label != null) {
                if (!isset($params[$field]) || $model->$field != $params[$field]) {
                    ee("{$label}【{$params[$keys[$keyIndex]]}】已存在，请重试");
                }
            }
            return $query;
        };
    }

    /**
     * 根据ID获取记录
     * @return \Closure
     */
    private static function getById(): \Closure
    {
        return function (int $id, ?bool $throw = true, ?bool $lock = false, ?string $msg = null) {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;
            $model = $query->when($lock, fn($q) => $q->lockForUpdate())->find($id);
            if (!$model && $throw) {
                $comment = $query->comment ?? '数据';
                ee($msg ?? "{$comment}不存在（{$id}）");
            }
            return $model;
        };
    }

    /**
     * NULL值条件查询
     * @return \Closure
     */
    private static function ifIsNull(): \Closure
    {
        return function (array $params, string $key, ?string $field = null) {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;
            $field = $field ?? $key;
            return $query->when(
                array_key_exists($key, $params),
                fn($q) => $q->when(
                    $params[$key],
                    fn($q) => $q->whereNull($field),
                    fn($q) => $q->whereNotNull($field)
                )
            );
        };
    }

    /**
     * NOT NULL值条件查询
     * @return \Closure
     */
    private static function ifIsNotNull(): \Closure
    {
        return function (array $params, string $key, ?string $field = null) {
            /** @var QueryBuilder|EloquentBuilder $query */
            $query = $this;
            $field = $field ?? $key;
            return $query->when(
                array_key_exists($key, $params),
                fn($q) => $q->when(
                    $params[$key],
                    fn($q) => $q->whereNotNull($field),
                    fn($q) => $q->whereNull($field)
                )
            );
        };
    }
}