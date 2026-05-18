<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\File;

Route::get('/lotteries', function () {

    $json = File::get(
        storage_path('app/lottery.json')
    );

    $lotteries = json_decode($json, true);

    return response()->json($lotteries);
});