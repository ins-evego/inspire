<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return "OK";
});
Route::get('/privacy-policy', fn () => view('privacy-policy'));
