<?php

namespace Zuoge\LaravelToolsAi\Traits;

use Closure;

/**
 * @method static self ifWhere(array $params, string $key, ?string $field = null)
 * @method static self ifWhereLike(array $params, string $key, ?string $field = null)
 * @method static self ifWhereLikeKeyword(array $params, string $key, array $fields)
 * @method static self ifWhereNumberRange(array $params, string $key, ?string $field = null)
 * @method static self ifWhereDateRange(array $params, string $key, ?string $field = null, ?string $type = 'datetime')
 * @method static self ifHasWhereLike(array $params, string $key, string $relation, ?string $field = null)
 * @method static self order(?string $key = 'sorter', ?string $defaultField = 'id')
 * @method static unique(array $params, array $keys, string $label = null, string $field = 'id', ?int $keyIndex = 0)
 * @method static forSelect(?string $key1 = 'id', ?string $key2 = 'name', ?string $orderByDesc = 'id')
 * @method static page()
 * @method static self getById(int $id, ?bool $throw = true, ?bool $lock = false, ?string $msg = null)
 * @method static self ifIsNull(array $params, string $key, ?string $field = null)
 * @method static self ifIsNotNull(array $params, string $key, ?string $field = null)
 * @method static lockForUpdate()
 * @method static create(array $params)
 * @method static where(string $field, string $value, ?string $value)
 * @method static findOrFail(int $id)
 * @method static selectRaw(string $raw)
 * @method static whereIn(string $field, array $array)
 * @method static defaultOrder()
 * @method static updateOrCreate(string[] $array, array[] $array1)
 * @method static each(Closure $param)
 * @method children()
 * @method fixTree()
 * @method up()
 * @method down()
 * @method static whereNull(string $string)
 * @method static orderByDesc(string $string)
 * @method static ancestorsAndSelf(mixed $parentId)
 * @method syncPermissions(array $ids)
 * @method getAllPermissions()
 */
trait BaseModelTrait
{

}
