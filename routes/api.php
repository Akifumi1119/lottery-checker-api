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

Route::post('/check', function (Request $request) {

    $winningNumbers = [1, 5, 12, 18, 29, 41];

    $userNumbers = $request->numbers;

    $matchedNumbers = array_intersect(
        $winningNumbers,
        $userNumbers
    );

    $matchedCount = count($matchedNumbers);

    $rank = match ($matchedCount) {
        6 => '1等',
        5 => '2等',
        4 => '3等',
        3 => '4等',
        default => 'ハズレ'
    };

    return response()->json([
        'winning_numbers' => $winningNumbers,
        'your_numbers' => $userNumbers,
        'matched_numbers' => array_values($matchedNumbers),
        'matched_count' => $matchedCount,
        'rank' => $rank
    ]);
});