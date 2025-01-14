<?php

namespace Zuoge\LaravelToolsAi\Commands\GenFiles;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class BaseGenFileCommand extends Command
{
    /**
     * @return array[]
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::OPTIONAL, '名称参数'],
        ];
    }

    /**
     * @return array[]
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, '是否强制覆盖'],
        ];
    }

    /**
     * @return array{string, bool}
     */
    protected function getNameAndForce(): array
    {
        $name = $this->argument('name');
        $force = $this->option('force');
        if (!$name)
            ee("名称必须填写");
        return [$name, $force];
    }

    /**
     * @param string $stub
     * @param array $replaces
     * @param string $namespace
     * @param string $className
     * @param mixed $force
     * @return void
     */
    protected function GenFile(string $stub, array $replaces, string $namespace, string $className, bool $force): void
    {
        // 处理namespace
        $fileDir = $this->nameSpaceToDir($namespace);
        $filePath = $fileDir . DIRECTORY_SEPARATOR . $className . '.php';
        if (File::exists($filePath) && !$force) {
            $this->error($filePath . "\t文件已存在，如需覆盖，请加 -f 参数");
        } else {
            // 生成内容
            $subFilePath = [__DIR__, 'stubs', $stub];
            $content = File::get(implode(DIRECTORY_SEPARATOR, $subFilePath));
            $content = str_replace(array_keys($replaces), array_values($replaces), $content);
            // 写入文件
            File::put($filePath, $content);
            $this->info($filePath . "\t文件生成成功");
        }
    }

    /**
     * @param string $namespace
     * @return string
     */
    protected function nameSpaceToDir(string $namespace): string
    {
        $nsArray = explode('\\', $namespace);
        if ($nsArray[0] === 'App')
            $nsArray[0] = 'app';
        elseif ($nsArray[0] === 'Tests')
            $nsArray[0] = 'tests';
        $fileDir = implode(DIRECTORY_SEPARATOR, $nsArray);
        $fileDir = base_path($fileDir);
        if (!is_dir($fileDir))
            mkdir($fileDir, 0777, true);

        return $fileDir;
    }
}