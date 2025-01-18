<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;


Route::get('/', function () {
    return redirect('https://noticapp.ir');
});
Route::get('/cc', function () {
    Artisan::call('optimize:clear');

    return response()->json([
        'status'    => 'error',
        'message'   => 'The API endpoint not found or method not allowed.',
    ], 404);
});

// Fallback route for undefined routes
Route::any('{any}', function(){
    return response()->json([
        'status'    => 'error',
        'message'   => 'The API endpoint not found or method not allowed.',
    ], 404);
})->where('any', '.*');
