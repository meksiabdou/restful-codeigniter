<?php


$routes->group('api', ['namespace' => 'CI4Restful\Controllers\Api'], function($routes) {
    // Login/out
    $routes->post('login', 'Auth::login');

    $routes->post('logout', 'Auth::logout');

    // Registration
    $routes->post('register', 'Auth::register');

    // Activation
    //$routes->get('activate-account', 'Auth::activateAccount', ['as' => 'activate-account']);
    $routes->post('resend-activate-account', 'Auth::reSendActivateAccount', ['as' => 'resend-activate-account']);

    // Forgot/Resets
    $routes->post('forgot-password', 'Auth::forgot');
    $routes->post('reset-password', 'Auth::reset');


    //update data user
    $routes->post('update-user', 'Auth::update_user', ['as' => 'update-user']);
    //update password
    $routes->post('update', 'Auth::update_password', ['as' => 'update']);
});