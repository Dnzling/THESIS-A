<?php

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PhilippineHolidayService
{
    public function between(string $from, string $to): array
    {
        $start = CarbonImmutable::parse($from, 'Asia/Manila')->startOfDay();
        $end = CarbonImmutable::parse($to, 'Asia/Manila')->startOfDay();
        abort_if($end->lt($start) || $start->diffInDays($end) > 370, 422, 'Choose a holiday date range of 370 days or less.');

        $holidays = [];
        for ($year = $start->year; $year <= $end->year; $year++) {
            foreach ($this->forYear($year) as $holiday) {
                if ($holiday['date'] >= $start->toDateString() && $holiday['date'] <= $end->toDateString()) {
                    $holidays[] = $holiday;
                }
            }
        }

        return $holidays;
    }

    private function forYear(int $year): array
    {
        $cacheKey = "calendar.ph.holidays.{$year}";
        $staleKey = "calendar.ph.holidays.stale.{$year}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($year, $staleKey): array {
            try {
                $response = Http::acceptJson()->timeout(4)
                    ->get("https://nagerholidays.com/api/v4/Holidays/PH/{$year}");

                if (!$response->successful() || !is_array($response->json())) {
                    return Cache::get($staleKey, []);
                }

                $holidays = collect($response->json())
                    ->filter(fn ($holiday) => is_array($holiday)
                        && in_array('Public', $holiday['holidayTypes'] ?? [], true)
                        && !empty($holiday['date']))
                    ->map(fn (array $holiday) => [
                        'date' => (string) $holiday['date'],
                        'name' => (string) ($holiday['name'] ?? 'Philippine public holiday'),
                    ])
                    ->values()
                    ->all();
                Cache::forever($staleKey, $holidays);

                return $holidays;
            } catch (\Throwable) {
                return Cache::get($staleKey, []);
            }
        });
    }
}
