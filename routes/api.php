<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

$lotteryRoute = function (string $cacheKey, string $jsonFile) {
    Cache::increment($cacheKey);
    $path = storage_path("app/{$jsonFile}");
    if (!File::exists($path)) {
        return response()->json(['error' => 'データが見つかりません。lottery:fetch を実行してください。'], 404);
    }
    return response()->json(json_decode(File::get($path), true));
};

Route::get('/lotteries', function () use ($lotteryRoute) {
    return $lotteryRoute('access_lotteries', 'lottery.json');
});

Route::get('/lotteries/zenkoku', function () use ($lotteryRoute) {
    return $lotteryRoute('access_lotteries_zenkoku', 'zenkoku.json');
});

Route::get('/lotteries/tokyo', function () use ($lotteryRoute) {
    return $lotteryRoute('access_lotteries_tokyo', 'tokyo.json');
});

Route::get('/lotteries/kct', function () use ($lotteryRoute) {
    return $lotteryRoute('access_lotteries_kct', 'kct.json');
});

Route::get('/lotteries/kinki', function () use ($lotteryRoute) {
    return $lotteryRoute('access_lotteries_kinki', 'kinki.json');
});

Route::get('/lotteries/nishinihon', function () use ($lotteryRoute) {
    return $lotteryRoute('access_lotteries_nishinihon', 'nishinihon.json');
});

Route::get('/lotteries/chiiki', function () use ($lotteryRoute) {
    return $lotteryRoute('access_lotteries_chiiki', 'chiiki.json');
});

Route::get('/stats', function () {
    $keys = [
        'lotteries'           => 'access_lotteries',
        'lotteries/zenkoku'   => 'access_lotteries_zenkoku',
        'lotteries/tokyo'     => 'access_lotteries_tokyo',
        'lotteries/kct'       => 'access_lotteries_kct',
        'lotteries/kinki'     => 'access_lotteries_kinki',
        'lotteries/nishinihon'=> 'access_lotteries_nishinihon',
        'lotteries/chiiki'    => 'access_lotteries_chiiki',
    ];

    $counts = [];
    foreach ($keys as $endpoint => $key) {
        $counts[$endpoint] = (int) Cache::get($key, 0);
    }
    $counts['total'] = array_sum($counts);

    return response()->json($counts);
});
