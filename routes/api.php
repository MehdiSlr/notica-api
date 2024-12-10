<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
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
Route::controller(CompanyController::class)
    ->middleware(['api'])
    ->prefix('companies')
    ->name('api.companies.')
    ->missing(function (Request $request) {
        return response()->json([
            'type' => 'error',
            'message' => 'company not found.',
        ], 404);
    })
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/trash', 'onlyTrash')->name('onlyTrash');
        Route::get('/with-trash', 'withTrash')->name('withTrash');
        Route::get('/{company}', 'show')->name('show');
        Route::patch('/{company}', 'update')->name('update');
        Route::patch('/{company}/restore', 'restore')->name('restore');
        Route::delete('/{company}', 'delete')->name('delete');
    });

    //Users
    Route::controller(UserController::class)
    ->middleware(['api'])
    ->prefix('users')
    ->name('api.users.')
    ->missing(function (Request $request) {
        return response()->json([
            'type' => 'error',
            'message' => 'user not found.',
        ], 404);
    })
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/trash', 'onlyTrash')->name('onlyTrash');
        Route::get('/with-trash', 'withTrash')->name('withTrash');
        Route::get('/{user}', 'show')->name('show');
        Route::patch('/{user}', 'update')->name('update');
        Route::patch('/{user}/restore', 'restore')->name('restore');
        Route::delete('/{user}', 'delete')->name('delete');
    });

    //Tickets
    Route::controller(TicketController::class)
    ->middleware(['api'])
    ->prefix('tickets')
    ->name('api.tickets.')
    ->missing(function (Request $request) {
        return response()->json([
            'type' => 'error',
            'message' => 'ticket not found.',
        ], 404);
    })
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{ticket}', 'show')->name('show');
        Route::patch('/{ticket}', 'update')->name('update');
        Route::post('/upload', 'upload')->name('upload');
    });