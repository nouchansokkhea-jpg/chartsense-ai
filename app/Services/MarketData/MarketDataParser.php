<?php

namespace App\Services\MarketData;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;

class MarketDataParser
{
    /**
     * Parse and validate uploaded CSV file containing OHLCV records.
     *
     * @return array{candles: list<array>, count: int, symbol: ?string, timeframe: ?string, start: Carbon, end: Carbon}
     * @throws Exception
     */
    public function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            throw new Exception('Unable to read market data file.');
        }

        // Read header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new Exception('CSV file is empty.');
        }

        // Normalize header columns
        $headerMap = $this->mapHeaders($header);

        $candles = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            if (count($row) < 5) {
                continue;
            }

            $rawTimestamp = $row[$headerMap['timestamp']] ?? null;
            $rawOpen = $row[$headerMap['open']] ?? null;
            $rawHigh = $row[$headerMap['high']] ?? null;
            $rawLow = $row[$headerMap['low']] ?? null;
            $rawClose = $row[$headerMap['close']] ?? null;
            $rawVolume = isset($headerMap['volume']) ? ($row[$headerMap['volume']] ?? 0) : 0;

            if ($rawTimestamp === null || $rawOpen === null || $rawHigh === null || $rawLow === null || $rawClose === null) {
                continue;
            }

            try {
                $timestamp = Carbon::parse($rawTimestamp);
            } catch (Exception $e) {
                // Try unix timestamp if numeric
                if (is_numeric($rawTimestamp)) {
                    $timestamp = Carbon::createFromTimestamp((int)$rawTimestamp);
                } else {
                    continue;
                }
            }

            $open = (float)str_replace(',', '', $rawOpen);
            $high = (float)str_replace(',', '', $rawHigh);
            $low = (float)str_replace(',', '', $rawLow);
            $close = (float)str_replace(',', '', $rawClose);
            $volume = (float)str_replace(',', '', $rawVolume);

            // OHLC consistency checks
            if ($high < $low) {
                $temp = $high;
                $high = $low;
                $low = $temp;
            }

            if ($high < max($open, $close)) {
                $high = max($open, $close);
            }

            if ($low > min($open, $close)) {
                $low = min($open, $close);
            }

            $candles[] = [
                'timestamp' => $timestamp->toIso8601String(),
                'date' => $timestamp->format('Y-m-d H:i'),
                'open' => round($open, 5),
                'high' => round($high, 5),
                'low' => round($low, 5),
                'close' => round($close, 5),
                'volume' => round($volume, 2),
            ];
        }

        fclose($handle);

        if (count($candles) < 10) {
            throw new Exception('At least 10 valid candlestick records are required for technical analysis. Found: '.count($candles));
        }

        // Sort chronologically ascending
        usort($candles, fn($a, $b) => strcmp($a['timestamp'], $b['timestamp']));

        return [
            'candles' => $candles,
            'count' => count($candles),
            'start' => Carbon::parse($candles[0]['timestamp']),
            'end' => Carbon::parse(end($candles)['timestamp']),
        ];
    }

    /**
     * Map header variations (e.g., Date, Time, Open, High, Low, Close, Volume).
     */
    protected function mapHeaders(array $header): array
    {
        $map = [
            'timestamp' => 0,
            'open' => 1,
            'high' => 2,
            'low' => 3,
            'close' => 4,
            'volume' => 5,
        ];

        foreach ($header as $idx => $col) {
            $colLower = strtolower(trim($col));

            if (in_array($colLower, ['time', 'date', 'timestamp', 'datetime', 'gmt time'])) {
                $map['timestamp'] = $idx;
            } elseif ($colLower === 'open' || $colLower === 'o') {
                $map['open'] = $idx;
            } elseif ($colLower === 'high' || $colLower === 'h') {
                $map['high'] = $idx;
            } elseif ($colLower === 'low' || $colLower === 'l') {
                $map['low'] = $idx;
            } elseif ($colLower === 'close' || $colLower === 'c') {
                $map['close'] = $idx;
            } elseif (in_array($colLower, ['vol', 'volume', 'v', 'tick_volume'])) {
                $map['volume'] = $idx;
            }
        }

        return $map;
    }

    /**
     * Parse raw string of pasted text (CSV or JSON).
     */
    public function parseRawText(string $rawText): array
    {
        $rawText = trim($rawText);

        // Check if JSON
        if (str_starts_with($rawText, '[') && str_ends_with($rawText, ']')) {
            $data = json_decode($rawText, true);
            if (is_array($data) && count($data) >= 10) {
                return [
                    'candles' => $data,
                    'count' => count($data),
                    'start' => Carbon::parse($data[0]['timestamp'] ?? now()),
                    'end' => Carbon::parse(end($data)['timestamp'] ?? now()),
                ];
            }
        }

        // Parse line by line as CSV
        $lines = explode("\n", $rawText);
        $header = str_getcsv(array_shift($lines));
        $headerMap = $this->mapHeaders($header);

        $candles = [];
        foreach ($lines as $line) {
            $row = str_getcsv(trim($line));
            if (count($row) < 5) continue;

            $timestamp = Carbon::parse($row[$headerMap['timestamp']] ?? now());
            $open = (float)($row[$headerMap['open']] ?? 0);
            $high = (float)($row[$headerMap['high']] ?? 0);
            $low = (float)($row[$headerMap['low']] ?? 0);
            $close = (float)($row[$headerMap['close']] ?? 0);
            $volume = (float)($row[$headerMap['volume']] ?? 0);

            $candles[] = [
                'timestamp' => $timestamp->toIso8601String(),
                'date' => $timestamp->format('Y-m-d H:i'),
                'open' => round($open, 5),
                'high' => round($high, 5),
                'low' => round($low, 5),
                'close' => round($close, 5),
                'volume' => round($volume, 2),
            ];
        }

        if (count($candles) < 10) {
            throw new Exception('Pasted data must contain at least 10 valid candles.');
        }

        usort($candles, fn($a, $b) => strcmp($a['timestamp'], $b['timestamp']));

        return [
            'candles' => $candles,
            'count' => count($candles),
            'start' => Carbon::parse($candles[0]['timestamp']),
            'end' => Carbon::parse(end($candles)['timestamp']),
        ];
    }
}
