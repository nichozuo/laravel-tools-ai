<?php

namespace Zuoge\LaravelToolsAi\Commands\GenFiles;

use Exception;
use Illuminate\Support\Str;
use Zuoge\LaravelToolsAi\Collections\RouterCollection;

class GenControllerTestFileCommand extends BaseGenFileCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'gt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "根据输入的路径，生成控制器的测试文件，包含所有方案和请求参数。路径通过斜杠/拆分成[模块名]和[表名]。
    模块名：会转成大写开头的驼峰，斜杠/分割成数组，支持多级目录；
    表名：会转成大写开头的驼峰；
    例如：php artisan gt admin/users
    例如：php artisan gt Admin/auth/CompanyAdmin";

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        list($name, $force) = $this->getNameAndForce();

        $modulesName = Str::of($name)->explode('/')->map(function ($item) {
            return Str::of($item)->replace('Controller', '')->studly()->toString();
        });

        $modelName = Str::of($modulesName->pop())->studly()->plural()->toString();

        $testNamespace = "Tests\\Modules\\" . $modulesName->implode('\\');
        $appNamespace = "App\\Modules\\" . $modulesName->implode('\\');

        $testClassName = $modelName . 'ControllerTest';
        $appFullClassName = "$appNamespace\\{$modelName}Controller";

        $replaces = [
            '{{ namespace }}' => $testNamespace,
            '{{ appFullClassName }}' => $appFullClassName,
            '{{ modelName }}' => $modelName,
            '{{ functions }}' => $this->getFunctions($appFullClassName),
        ];

        $this->GenFile('test.stub', $replaces, $testNamespace, $testClassName, $force);
    }

    /**
     * @param $fullClassName
     * @return string
     */
    private function getFunctions($fullClassName): string
    {
        $router = RouterCollection::getInstance()->getByControllerClass($fullClassName);
        $stringBuilder = [];
        foreach ($router->actions as $action) {
            $params = [];
            foreach ($action->requestBody['properties'] ?? [] as $paramName => $paramDetails) {
                $params[] = "'$paramName' => '', # {$paramDetails['description']}";
            }
            $paramsStr = implode(",\n\t\t\t", $params);
            $functionName = 'test' . Str::of($action->action)->camel()->ucfirst();
            $stringBuilder[] = "public function $functionName()
    {
        \$this->go(__METHOD__, [
            $paramsStr
        ]);
    }";
        }
        return implode("\n\n\t", $stringBuilder);
    }
}
