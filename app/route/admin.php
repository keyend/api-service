<?php
namespace app\route;
/**
 * 管理控制端路由
 * @package app\route
 */
use think\facade\Route;
use app\middleware\Authorization;

Route::group('admin', function() {
    Route::group("login", function() {
        Route::rule("login", '/login', 'POST|GET')->name("login");
        Route::get("captcha", '/captcha')->name("loginCaptcha");
    })->prefix("admin.login");
})->prefix("admin");
