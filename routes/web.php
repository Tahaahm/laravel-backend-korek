<?php

use App\Http\Controllers\ElasticsearchController;
use App\Http\Controllers\test;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PersonController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::view('upload','upload');
Route::get('/create', [ElasticsearchController::class, 'createIndex']);
Route::post('/index', [ElasticsearchController::class, 'uploadAndIndex']);
Route::get('/search', [ElasticsearchController::class,'search']);


Route::post('/people', [PersonController::class ,'store']);
Route::get('/people', [PersonController::class ,'index']);


Route::get('/fetch',[ElasticsearchController::class,'getAllTitlesAndContent']);
Route::get('/createindex',[ElasticsearchController::class,'createPdfAttachmentPipeline']);
Route::delete('/delete', [ElasticsearchController::class,'deleteDocumentsByTitle']);
