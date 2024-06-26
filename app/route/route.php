<?php
namespace app\route;
use think\facade\Route;

Route::get("/", function() {
    return redirect("/admin/")->send();
});