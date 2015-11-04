<?php


/**
 * Login Page
 * ----------
 *
 * [1]. manager/login
 *
 */

Route::accept($config->manager->slug . '/login', function() use($config, $speak) {
    if( ! File::exist(DECK . DS . 'launch.php')) {
        Shield::abort('404-manager');
    }
    if(Guardian::happy()) {
        Guardian::kick($config->manager->slug . '/article');
    }
    $cargo = 'cargo.login.php';
    Config::set('page_title', $speak->log_in . $config->title_separator . $config->manager->title);
    include DECK . DS . 'workers' . DS . 'cargo.php';
    if($request = Request::post()) {
        Guardian::authorize()->kick(isset($request['kick']) ? $request['kick'] : $config->manager->slug . '/article');
    }
    Shield::attach('manager-login');
}, 20);


/**
 * Logout Page
 * -----------
 *
 * [1]. manager/logout
 *
 */

Route::accept($config->manager->slug . '/logout', function() use($config, $speak) {
    Notify::success(ucfirst(strtolower($speak->logged_out)) . '.');
    Guardian::reject()->kick($config->manager->slug . '/login');
}, 21);