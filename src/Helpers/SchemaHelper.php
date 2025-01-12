<?php

namespace Zuoge\LaravelToolsAi\Helpers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
// use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Illuminate\Database\Schema\ForeignKeyDefinition;
use ReflectionEnum;
use ReflectionException;

class SchemaHelper
{
    /**
     * 标准枚举字段
     * @param Blueprint $table
     * @param string $field
     * @param string $enumClass
     * @param string $comment
     * @return ColumnDefinition
     * @throws ReflectionException
     */
    public static function Enum(Blueprint $table, string $field, string $enumClass, string $comment): ColumnDefinition
    {
        $enum = new ReflectionEnum($enumClass);
        $className = str_replace('App\\Enums\\', '', $enumClass);

        $values = array_map(function ($item) {
            return $item->getValue()->value;
        }, $enum->getCases());

        return $table->enum($field, $values)
            ->comment("$comment,[enum:$className]");
    }

    /**
     * 标准外键字段
     * @param Blueprint $table
     * @param string $field
     * @param string $comment
     * @param string|null $referenceTable
     * @return ForeignKeyDefinition
     */
    public static function ForeignId(Blueprint $table, string $field, string $comment, ?string $referenceTable = null): ForeignKeyDefinition
    {
        $referenceTable = $referenceTable ?? str_replace('_id', '', $field);
        return $table->foreignId($field)
            ->constrained($referenceTable)
            ->onDelete('no action')
            ->onUpdate('no action')
            ->comment("{$comment}id,[ref:$referenceTable]");
    }
}
