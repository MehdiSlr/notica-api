<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;


Route::get('/', function () {
    return redirect('https://noticapp.ir');
});

Route::get('/cc', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('event:clear');
    Artisan::call('route:clear');
    Artisan::call('optimize');

    Cache::flush();
    cache()->flush();

    $exitCode = Artisan::call('optimize:clear');
    $responseCode = $exitCode === 0 ? 200 : 500;
    return response()->json([
        'status'    => 'error',
        'message'   => 'The API endpoint not found or method not allowed.',
    ], $responseCode);
});

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

Route::get('/api-documentation', function () {
    Artisan::call('scramble:export');
    return view('scramble::docs', [
        'spec' => file_get_contents(base_path('api.json')),
        'config' => Scramble::getGeneratorConfig('default'),
    ]);
})
->middleware(Scramble::getGeneratorConfig('default')->get('middleware', [RestrictedDocsAccess::class]));

// Fallback route for undefined routes
Route::any('{any}', function(){
    return response()->json([
        'status'    => 'error',
        'message'   => 'The API endpoint not found or method not allowed.',
    ], 404);
})->where('any', '.*');
