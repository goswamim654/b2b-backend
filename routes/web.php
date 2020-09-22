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

    // Matches "/api/changePassword
    $router->post('changePassword', 'AuthController@changePassword');

    // Matches "/api/profile
    $router->get('profile', 'UserController@profile');

    // Matches "/api/profileUpdate
    $router->post('profileUpdate', 'UserController@profileUpdate');

    // Matches "/api/Cities
    $router->get('getCities/[{state}]', 'UserController@getCities');


    // Matches "/api/users/1 
    //get one user by id
    $router->get('users/{id}', 'UserController@singleUser');

    // Matches "/api/users
    $router->get('users', 'UserController@allUsers');

    // Matches "/api/preferences
    $router->post('updatePreferences', 'PreferenceContoller@updatePreferences');

    // Matches "/api/preferences
    $router->get('getPreferences', 'PreferenceContoller@getPreferences');

    // Matches "/api/preferences
    $router->get('getProductCategories', 'PreferenceContoller@getProductCategories');


    //product routes
    $router->get('products', 'ProductsController@index');
    $router->get('products/[{id}]', 'ProductsController@show');
    $router->put('products/[{id}]', 'ProductsController@update');
    $router->post('products', 'ProductsController@store');
    $router->post('deleteProductImage', 'ProductsController@deleteProductImage');

    $router->get('getImages', 'ProductsController@getImages');


    // Matches "/api/availability product availibilty
    $router->post('updateProductAvalibility', 'ProductsController@updateProductAvalibility');

    // Matches "/api/updateProductStatus product status
    $router->post('updateProductStatus', 'ProductsController@updateProductStatus');

    // preview product
    $router->post('previewProducts', 'ProductsController@previewProducts');
    $router->get('previewProductsDetails/[{id}]', 'ProductsController@previewProductsDetails');

    $router->post('saveImages', 'ProductsController@saveImages');
});
