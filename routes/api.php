<?php

use Illuminate\Support\Facades\Route;
use Zuoge\LaravelToolsAi\Http\Controllers\DocsController;

// 生成API文档
Route::middleware('api')->get('api/docs/openapi', [DocsController::class, 'openapi']);
