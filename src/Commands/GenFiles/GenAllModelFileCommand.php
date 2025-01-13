<?php

namespace Zuoge\LaravelToolsAi\Commands\GenFiles;

use Illuminate\Console\Command;
use Zuoge\LaravelToolsAi\Collections\DBTableCollection;

class GenAllModelFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'gad';

    /**
     * The console command description.
     * @var string
     */
    protected $description = '生成所有数据库表对应的模型文件';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $dbModel = DBTableCollection::getInstance()->getCollection();
        foreach ($dbModel->tables as $table) {
            $this->call('gd', ['table_name' => $table->name]);
        }
        $this->info('生成完成');
    }
}
