<?php

declare(strict_types=1);

namespace Zuoge\LaravelToolsAi\Http\Middleware;

use Closure;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

class JsonResponseMiddleware
{
    /**
     * 处理请求
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 获取原始响应内容
        $data = $response->original ?? null;
        // 如果响应中包含了success, errorMessage, status, 则直接返回
        if (isset($data['success']) && isset($data['errorMessage']) && isset($data['status'])) {
            return response()->json($data, $data['status']);
        }

        // 如果是分页数据
        if ($data instanceof LengthAwarePaginator) {
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $data->items(),
                    'total' => $data->total(),
                    'page' => $data->currentPage(),
                    'per_page' => $data->perPage(),
                ],
            ]);
        }

        // 如果是模型数据、集合数据、数组
        if ($data instanceof Arrayable) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        // 如果是null（通常是删除或者不需要返回数据的操作）
        if ($data === null) {
            return response()->json([
                'success' => true,
            ]);
        }

        // 其他情况，直接包装数据
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
