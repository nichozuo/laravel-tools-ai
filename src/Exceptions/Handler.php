<?php

namespace Zuoge\LaravelToolsAi\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class Handler extends ExceptionHandler
{
    /**
     * 注册应用程序的异常处理回调
     */
    public function register(): void
    {
        // 不需要在这里注册任何回调
    }

    /**
     * 将异常渲染为HTTP响应
     *
     * @param  Request  $request
     * @param Throwable $e
     * @return JsonResponse
     */
    public function render($request, Throwable $e): JsonResponse
    {
        return $this->handleApiException($e);
    }

    /**
     * 将身份验证异常转换为响应
     * 
     * 注意：这里我们直接返回JSON响应，而不是重定向到登录页面
     *
     * @param  Request  $request
     * @param AuthenticationException $exception
     * @return JsonResponse
     */
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse
    {
        return $this->handleApiException($exception);
    }

    /**
     * 将验证异常转换为JSON响应
     *
     * @param  Request  $request
     * @param ValidationException $exception
     * @return JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception): JsonResponse
    {
        return $this->handleApiException($exception);
    }

    /**
     * 处理API异常并返回JSON响应
     *
     * @param Throwable $e
     * @return JsonResponse
     */
    private function handleApiException(Throwable $e): JsonResponse
    {
        $debug = config('app.debug');

//        $status = 500;

        // 获取异常配置
        $defaults = [
            'success' => false,
            'errorCode' => $e->getCode(),
            'errorMessage' => $e->getMessage(),
            'errorDetail' => []
        ];

        // 使用 get_class($e) 进行异常类型匹配
        $config = $defaults;
        switch (get_class($e)) {
            case AuthenticationException::class:
//                $status = 401;
                $config = array_merge($defaults, [
                    'errorMessage' => '未经授权'
                ]);
                break;
            case AuthorizationException::class:
//                $status = 403;
                $config = array_merge($defaults, [
                    'errorMessage' => '没有权限执行此操作'
                ]);
                break;
            case ValidationException::class:
//                $status = 422;
                $config = array_merge($defaults, [
                    'errorMessage' => '数据验证失败',
                    'errorDetail' => $e->errors()
                ]);
                break;
            case ModelNotFoundException::class:
            case NotFoundHttpException::class:
//                $status = 404;
                $config = array_merge($defaults, [
                    'errorMessage' => '请求的资源不存在'
                ]);
                break;
            case QueryException::class:
//                $status = 500;
                $config = array_merge($defaults, [
                    'errorMessage' => '数据库操作失败'
                ]);
                break;
            case HttpException::class:
//                $status = $e->getStatusCode();
                $config = array_merge($defaults, [
                    'errorMessage' => $e->getMessage()
                ]);
                break;
            default:
                // 保持默认配置
                break;
        }

        // 在开发环境下添加详细错误信息
        if ($debug) {
            $trace = collect($e->getTrace())->map(function ($trace) {
                // 过滤掉一些不必要的信息
                if (str_contains($trace['file'] ?? '', 'laravel/framework')) {
                    return null;
                }
                return [
                    'file' => $trace['file'] ?? null,
                    'line' => $trace['line'] ?? null,
                    'function' => $trace['function'] ?? null,
                    'class' => $trace['class'] ?? null,
                    'type' => $trace['type'] ?? null,
                    'args' => isset($trace['args']) ? $this->formatArgs($trace['args']) : null,
                ];
            })->filter()->all();

            // 获取请求信息
            $request = request();
            $requestInfo = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'input' => $this->filterRequestInput($request->all()),
                'headers' => $this->filterHeaders($request->headers->all()),
            ];

            $config['debug'] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $requestInfo,
                'trace' => $trace
            ];
        }
        return response()->json($config);
    }

    /**
     * 过滤请求输入，移除敏感信息
     *
     * @param array $input
     * @return array
     */
    private function filterRequestInput(array $input): array
    {
        $sensitiveKeys = config('common.exception_handler.sensitive_keys', []);

        return collect($input)->map(function ($value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                return '******';
            }
            if (is_array($value)) {
                return $this->filterRequestInput($value);
            }
            return $value;
        })->all();
    }

    /**
     * 过滤请求头，只保留指定的请求头
     *
     * @param array $headers
     * @return array
     */
    private function filterHeaders(array $headers): array
    {
        $allowedHeaders = config('common.exception_handler.allowed_headers', []);

        return collect($headers)
            ->filter(function ($value, $key) use ($allowedHeaders) {
                $key = strtolower($key);
                return in_array($key, $allowedHeaders);
            })
            ->all();
    }

    /**
     * 格式化参数，避免复杂对象导致的JSON编码问题
     *
     * @param array $args
     * @return array
     */
    private function formatArgs(array $args): array
    {
        return array_map(function ($arg) {
            if (is_object($arg)) {
                return get_class($arg);
            } elseif (is_array($arg)) {
                return 'array';
            } elseif (is_resource($arg)) {
                return 'resource';
            } elseif (is_string($arg) && strlen($arg) > 100) {
                return substr($arg, 0, 100) . '...';
            }
            return $arg;
        }, $args);
    }
}