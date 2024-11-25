<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

function _createUUidCode(): string
{
    return time() . now()->microsecond;
}

function _createStrUuid($strLength = 5): string
{
    return Str::random($strLength) . '_' . now()->microsecond;
}

function _logger($status, $model, $method, $data, $oldData = null): void
{
    if (is_null($oldData)) {
        Log::info("status : {status} , {modelName} : {methodName} , data : {data} ", [
            'status' => $status,
            'modelName' => $model,
            'methodName' => $method,
            'data' => $data,
        ]);
        return;
    }

    Log::info("status : {status} , {modelName} : {methodName} , new data : {data} , old data : {oldData}", [
        'status' => $status,
        'modelName' => $model,
        'methodName' => $method,
        'data' => $data,
        'oldData' => $oldData
    ]);
}
