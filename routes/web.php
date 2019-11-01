<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Jobs\ProcessMetaData;
use App\Services\OpportunityServices;
use App\Sources\SharpSpringSource;
use Illuminate\Support\Facades\DB;

Route::post('/import', 'AdminController@importFile')->name('admin.import');
Route::get('/import/process', 'AdminController@processFile')->name('admin.import.process');
Route::get('/process/metadata/sharpspring/{type?}', 'SharpSpringController@metaDataProcess')->name('sharps-spring.metadata.process');

Route::get('/teste',function(){
    $source = new OpportunityServices();
    $source->processeMetaData();
});

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/import-leads', function () {
    return view('leads',['metaDataFile'=>[],'metaData'=>[]]);
})->name('leads');
