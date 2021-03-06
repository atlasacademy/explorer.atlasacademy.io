<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/refresh', 'RefreshController@trigger');

$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->get('/', 'HomeController@index');
    $router->get('/{bucketName}', 'DirectoryController@root');
    $router->get('/{bucketName}/{path:.+}', 'DirectoryController@display');
});
