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

$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->group(['prefix' => 'checklists'], function() use ($router) {
        $router->get('{checklistId}', ['as' => 'checklists.detail', 'uses' => 'ChecklistController@detail']);
        $router->patch('{checklistId}', 'ChecklistController@update');
        $router->delete('{checklistId}', 'ChecklistController@delete');
        $router->post('/', 'ChecklistController@create');
        $router->get('/', ['as' => 'checklists', 'uses' => 'ChecklistController@all']);

        $router->post('complete', 'ItemController@complete');
        $router->post('incomplete', 'ItemController@incomplete');
        $router->group(['prefix' => '{checklistId}/items'], function() use ($router) {
            $router->get('/', ['as' => 'items', 'uses' => 'ChecklistController@items']);
            $router->post('/', 'ItemController@create');
            $router->get('{itemId}', ['as' => 'items.detail', 'uses' => 'ItemController@detail']);
            $router->patch('{itemId}', 'ItemController@update');
            $router->delete('{itemId}', 'ItemController@delete');
            $router->post('_bulk', 'ItemController@bulk');
        });

        $router->get('items/summaries', 'ChecklistController@summary');
        // $router->group(['prefix' => 'histories'], function() use ($router) {
        //     $router->get('/', 'ChecklistController@histories');
        //     $router->get('{historyId}', 'ChecklistController@history');
        // });
    });
});

