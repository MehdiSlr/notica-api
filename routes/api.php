<?php

use App\Http\Controllers\PlanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Plans
Route::controller(PlanController::class)
    ->middleware(['api'])
    ->prefix('plans')
    ->name('api.plans.')
    ->missing(function (Request $request) {
        return response()->json([
            'type' => 'error',
            'message' => 'plan not found.',
        ], 404);
    })
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/trash', 'onlyTrash')->name('onlyTrash');
        Route::get('/with-trash', 'withTrash')->name('withTrash');
        Route::get('/{plan}', 'show')->name('show');
        Route::patch('/{plan}', 'update')->name('update');
        Route::patch('/{plan}/restore', 'restore')->name('restore');
        Route::delete('/{plan}', 'delete')->name('delete');
        Route::delete('/{plan}/destroy', 'destroy')->name('destroy');
    });

// Companies