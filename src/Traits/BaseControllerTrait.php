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



    /**
     * @param mixed $user
     * @param array $params
     * @return void
     * @throws Err
     */
    protected function doChangePassword(mixed $user, array $params): void
    {
        if ($params['new_password'] != $params['re_new_password'])
            ee("两次密码输入不一致");
        if (!$user || !Hash::check($params['old_password'], $user->password)) {
            ee("修改失败：原密码错误");
        }
        $user->update([
            'password' => bcrypt($params['new_password'])
        ]);
    }
}
