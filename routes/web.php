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
$router->get('test-email', 'UserController@testEmail');

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

    // Matches "/api/send-reset-password-link

    $router->post('sendResetPasswordLink', 'AuthController@sendResetPasswordLink');

    // Matches "/api/reset-password

    $router->post('resetPassword', 'AuthController@resetPassword');

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

    // Matches "/api/getSellers
    $router->get('getSellers', 'UserController@getSellers');

    // Matches "/api/getBuyers
    $router->get('getBuyers', 'UserController@getBuyers');

    // Matches "/api/updateUserType
    $router->get('updateUserType', 'UserController@updateUserType');


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

    $router->get('getProducts/[{user_id}]', 'ProductsController@getProducts');

    $router->get('getImages', 'ProductsController@getImages');


    // Matches "/api/availability product availibilty
    $router->post('updateProductAvalibility', 'ProductsController@updateProductAvalibility');

    // Matches "/api/updateProductStatus product status
    $router->post('updateProductStatus', 'ProductsController@updateProductStatus');

    // preview product
    $router->post('previewProducts', 'ProductsController@previewProducts');
    $router->get('previewProductsDetails/[{id}]', 'ProductsController@previewProductsDetails');

    $router->post('saveImages', 'ProductsController@saveImages');

    $router->get('search/[{keyword}]', 'ProductsController@search');

    $router->post('getSearchResult', 'ProductsController@getSearchResult');

    // orders
    $router->get('orders', 'OrderController@index');
    $router->get('getOrders/[{user_id}]', 'OrderController@getOrders');
    $router->get('getOrdersByStatus', 'OrderController@getOrdersByStatus');
    // $router->get('products/[{id}]', 'ProductsController@show');
    // $router->put('products/[{id}]', 'ProductsController@update');
    $router->post('orders', 'OrderController@store');
    $router->get('deleteOrder/[{id}]', 'OrderController@deleteOrder');
    $router->get('getOrdersBySeller', 'OrderController@getOrdersBySeller');

    $router->get('getOrdersByStatusView', 'OrderController@getOrdersByStatusView');
    $router->get('orderViewed', 'OrderController@orderViewed');

    // message
    $router->get('messages', 'MessageController@index');
    $router->post('messages', 'MessageController@store');
    $router->get('messages/[{id}]', 'MessageController@update');
    $router->get('getMessageByOrder/[{id}]', 'MessageController@getMessageByOrder');

    //address
    
    $router->get('getDefaultAddress', 'AddressController@getDefaultAddress');
    $router->get('getAddressByOrder/[{id}]', 'AddressController@show');
    $router->post('updateAddress', 'AddressController@store');
    
    // categories
    
    $router->get('getCategories', 'CategoriesController@getCategories');

});
