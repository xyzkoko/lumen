<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});
$app->post('/aa', function (){
    return 'aa';
});
$app->post('/task', 'AppController@task');
$app->get('/task', 'AppController@task');
$app->post('/task/result', 'AppController@taskResult');
$app->get('/task/result', 'AppController@taskResult');
$app->post('/app/list', 'AppController@appList');
$app->post('/backup', 'AppController@backup');
$app->post('/backup/get', 'AppController@getBackup');