<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\File;

Route::get('/lotteries', function () {
    $json = File::get(storage_path('app/lottery.json'));
    return response()->json(json_decode($json, true));
});

Route::get('/lotteries/zenkoku', function () {
    $path = storage_path('app/zenkoku.json');
    if (!File::exists($path)) {
        return response()->json(['error' => 'データが見つかりません。lottery:fetch を実行してください。'], 404);
    }
    return response()->json(json_decode(File::get($path), true));
});