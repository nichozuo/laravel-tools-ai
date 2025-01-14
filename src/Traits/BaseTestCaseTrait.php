<?php


namespace Zuoge\LaravelToolsAi\Traits;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

trait BaseTestCaseTrait
{
    protected ?string $baseUrl = null;
    protected array $tokens = [];

    /**
     * @intro 发起接口的请求
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     * @param bool|null $showDetail
     * @return void
     */
    protected function go(string $method, ?array $params = [], ?array $headers = [], ?bool $showDetail = false): void
    {
        $modules = str()->of($method)->replace('Tests\\Modules\\', '')->explode('\\')->toArray();
        $ctrlAndAction = array_pop($modules);
        $firstModule = $modules[0];
        //        $modulesName = implode('/', $modules);
        $modulesNameSnake = collect($modules)->map(function ($item) {
            return str()->of($item)->snake()->toString();
        })->implode('/');

        $arr = explode('ControllerTest::test', $ctrlAndAction);
        $ctrl = Str::of($arr[0])->snake()->toString();
        $action = Str::of($arr[1])->snake()->toString();

        $headers['Authorization'] = 'Bearer ' . str_replace('Bearer ', '', $this->tokens[$firstModule] ?? '');

        $uri = '/api/' . $modulesNameSnake . '/' . $ctrl . '/' . $action;
        if ($showDetail)
            dump('请求地址：', $uri);
        if ($showDetail)
            dump('请求参数：', $params);

        if (!$this->baseUrl) {
            $response = $this->post($uri, $params, $headers);
        } else {
            $response = Http::withHeaders($headers)->post($this->baseUrl . $uri, $params);
        }

        $json = $response->json();
        dump(json_encode($json));
        if ($showDetail) {
            dump("响应结果", $json);
        } else {
            dump($json);
        }
        $this->assertTrue($response->getStatusCode() == 200);
    }
}
