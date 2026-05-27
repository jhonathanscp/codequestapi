<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'codequestapi' => 'online',
        'version'      => '1.0'
    ]);
});
