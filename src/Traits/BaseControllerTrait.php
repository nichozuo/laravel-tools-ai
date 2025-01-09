<?php

namespace Zuoge\LaravelToolsAi\Traits;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

trait BaseControllerTrait
{
    /**
     * 获取分页大小
     */
    protected function getPerPage()
    {
        return request()->validate([
            'per_page' => ['nullable', 'integer', Rule::in(config('common.pagination.per_page_options'))],
        ])['per_page'] ?? config('common.pagination.default_per_page');
    }

    /**
     * 密码加密
     */
    protected function hashMake(array &$params, string $key = 'password'): void
    {
        if(isset($params[$key]))
            $params[$key] = Hash::make($params[$key]);
    }
}