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

Route::get('/lotteries/tokyo', function () {
    $path = storage_path('app/tokyo.json');
    if (!File::exists($path)) {
        return response()->json(['error' => 'データが見つかりません。lottery:fetch を実行してください。'], 404);
    }
    return response()->json(json_decode(File::get($path), true));
});

Route::get('/lotteries/kct', function () {
    $path = storage_path('app/kct.json');
    if (!File::exists($path)) {
        return response()->json(['error' => 'データが見つかりません。lottery:fetch を実行してください。'], 404);
    }
    return response()->json(json_decode(File::get($path), true));
});

Route::get('/lotteries/kinki', function () {
    $path = storage_path('app/kinki.json');
    if (!File::exists($path)) {
        return response()->json(['error' => 'データが見つかりません。lottery:fetch を実行してください。'], 404);
    }
    return response()->json(json_decode(File::get($path), true));
});

Route::get('/lotteries/nishinihon', function () {
    $path = storage_path('app/nishinihon.json');
    if (!File::exists($path)) {
        return response()->json(['error' => 'データが見つかりません。lottery:fetch を実行してください。'], 404);
    }
    return response()->json(json_decode(File::get($path), true));
});

Route::get('/lotteries/chiiki', function () {
    $path = storage_path('app/chiiki.json');
    if (!File::exists($path)) {
        return response()->json(['error' => 'データが見つかりません。lottery:fetch を実行してください。'], 404);
    }
    return response()->json(json_decode(File::get($path), true));
});