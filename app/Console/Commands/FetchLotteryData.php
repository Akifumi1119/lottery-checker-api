<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

// php artisan lottery:fetchでjson生成
class FetchLotteryData extends Command
{
    protected $signature = 'lottery:fetch';

    protected $description = '宝くじ当選番号取得';

    private function normalizeText(string $text): string {
        // 全角英数字 → 半角
        $text = mb_convert_kana($text, 'as');

        // 全角スペース除去
        $text = str_replace(['　', ' '], '', $text);

        // 前後空白除去
        $text = trim($text);

        return $text;
    }

    public function handle() {
        $csvPath = storage_path('app/jumbo.csv');

        $csv = file($csvPath);

        $result = [];

        $currentLottery = null;

        foreach ($csv as $line) {

            $line = mb_convert_encoding(
                trim($line),
                'UTF-8',
                'SJIS-win'
            );

            if (!$line) {
                continue;
            }

            $columns = str_getcsv($line);

            // 回情報
            if (
                isset($columns[0]) &&
                str_contains($columns[0], '第')
            ) {

                // 前回追加
                if ($currentLottery) {
                    $result[] = $currentLottery;
                }

                preg_match('/第(\d+)回/', $columns[0], $matches);

                $currentLottery = [
                    'round' => $this->normalizeText($matches[1] ?? ''),
                    'name' => $this->normalizeText($columns[1] ?? ''),
                    'draw_date' => $this->normalizeText($columns[2] ?? ''),
                    'prizes' => []
                ];

                continue;
            }

            // 等級情報
            if (
                isset($columns[0]) &&
                str_contains($columns[0], '等')
            ) {

                $currentLottery['prizes'][] = [
                    'rank' => $this->normalizeText($columns[0] ?? ''),
                    'amount' => $this->normalizeText($columns[1] ?? ''),
                    'rule' => $this->normalizeText($columns[2] ?? ''),
                    'number' => $this->normalizeText($columns[3] ?? ''),
                ];
            }
        }

        // 最後追加
        if ($currentLottery) {
            $result[] = $currentLottery;
        }

        file_put_contents(
            storage_path('app/lottery.json'),
            json_encode(
                $result,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            )
        );

        $this->info('JSON生成完了');
    }
}