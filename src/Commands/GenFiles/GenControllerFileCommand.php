<?php

namespace Zuoge\LaravelToolsAi\Commands\GenFiles;

use Exception;
use Zuoge\LaravelToolsAi\Collections\DBTableCollection;
use Zuoge\LaravelToolsAi\Models\DBTableModel;
use Illuminate\Support\Str;

class GenControllerFileCommand extends BaseGenFileCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'gc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "根据输入的路径，生成控制器文件。路径通过斜杠/拆分成[模块名]和[表名]。
    模块名：会转成大写开头的驼峰，斜杠/分割成数组，支持多级目录；
    表名：转蛇形；
    例如：php artisan gc admin/users
    例如：php artisan gc Admin/auth/CompanyAdmins";

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(): void
    {
        list($name, $force) = $this->getNameAndForce();

        $modulesName = Str::of($name)->explode('/')->map(function ($item) {
            return Str::of($item)->studly()->toString();
        })->toArray();

        $tableName = Str::of(array_pop($modulesName))->snake()->singular()->plural()->toString();

        $modelName = Str::of($tableName)->studly()->toString();

        $namespace = 'App\\Modules\\' . implode('\\', $modulesName);

        $table = DBTableCollection::getInstance()->getByName($tableName);

        $replaces = [
            '{{ namespace }}' => $namespace,
            '{{ modelName }}' => $modelName,
            '{{ comment }}' => $table->comment,
            '{{ validateString }}' => $table->getValidateString(),
        ];

        $this->GenFile('controller.stub', $replaces, $namespace, $modelName . 'Controller', $force);
    }
}
