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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// API route group
$router->group(['prefix' => 'api'], function () use ($router) {
    // Matches "/api/register
    $router->post('register', 'AuthController@register');

    // Matches "/api/verify_otp
    $router->post('verifyotp', 'AuthController@verifyOTP');

    // Matches "/api/resend_otp
    $router->post('resendOTP', 'AuthController@resendOTP');

    // Matches "/api/login
    $router->post('login', 'AuthController@login');

    // Matches "/api/profile
    $router->get('profile', 'UserController@profile');

    // Matches "/api/profileUpdate
    $router->post('profileUpdate', 'UserController@profileUpdate');

    // Matches "/api/users/1 
    //get one user by id
    $router->get('users/{id}', 'UserController@singleUser');

    // Matches "/api/users
    $router->get('users', 'UserController@allUsers');

    //product routes
    $router->get('products', 'ProductsController@index');
    $router->get('products/{id}', 'ProductsController@show');
    $router->put('products/{id}', 'ProductsController@update');
    $router->post('products', 'ProductsController@store');
    $router->delete('products/{id}', 'ProductsController@destroy');

});
