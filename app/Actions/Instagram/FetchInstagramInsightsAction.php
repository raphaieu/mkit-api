<?php

namespace App\Actions\Instagram;

use App\Models\InstagramInsight;
use App\Models\InstagramProfile;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Fetches insights from the Instagram Graph API and persists a snapshot.
 *
 * Requires the `instagram_manage_insights` permission (Phase 2 — App Review).
 * When the permission is not yet granted, the API returns OAuthException code 10
 * or 200. This action catches that error, logs a warning, and returns null so the
 * caller can skip insights without aborting the full sync.
 *
 * API version: v23.0
 *   Performance: period=day, response has `values[]` per metric.
 *   Demographics: metric=follower_demographics, period=lifetime, breakdown per call,
 *                 response has `total_value.breakdowns[].results[]`.
 *                 Requires ≥100 followers; returns empty data otherwise.
 *
 * Metrics available for Creator accounts (confirmed):
 *   reach, follower_count, accounts_engaged, total_interactions, profile_views
 *
 * Note: `views` (account-level impressions) is only returned for Business accounts.
 */
class FetchInstagramInsightsAction
{
    private const API_BASE = 'https://graph.instagram.com/v23.0';

    private const PERFORMANCE_METRICS = 'reach,follower_count,accounts_engaged,total_interactions,profile_views';

    /**
     * @return InstagramInsight|null  null when the permission is not available
     */
    public function execute(InstagramProfile $profile, string $token): ?InstagramInsight
    {
        // Performance metrics are critical — propagate any unexpected error.
        try {
            $performanceData = $this->fetchPerformanceMetrics($profile->instagram_id, $token);
        } catch (RequestException $e) {
            if ($this->isPermissionError($e)) {
                Log::warning('Instagram insights skipped: instagram_manage_insights not granted', [
                    'instagram_profile_id' => $profile->id,
                    'username'             => $profile->username,
                ]);

                return null;
            }

            throw $e;
        }

        // API returned an empty payload — no permission or no data yet; skip to
        // avoid persisting a row of zeros that would mislead the frontend.
        if (empty($performanceData)) {
            Log::info('Instagram insights skipped: empty performance data', [
                'instagram_profile_id' => $profile->id,
                'username'             => $profile->username,
            ]);

            return null;
        }

        // Demographic metrics are best-effort: "Not enough users" (3006) and
        // similar data-threshold errors return empty array instead of aborting.
        $genderAge = $this->fetchDemographicBreakdownSafe($profile->instagram_id, $token, 'age,gender');
        $country   = $this->fetchDemographicBreakdownSafe($profile->instagram_id, $token, 'country');
        $city      = $this->fetchDemographicBreakdownSafe($profile->instagram_id, $token, 'city');

        $parsed = $this->parsePerformanceMetrics($performanceData);

        return InstagramInsight::updateOrCreate(
            ['instagram_profile_id' => $profile->id],
            [
                'synced_at'                => now(),
                'accounts_engaged_28d'     => $parsed['accounts_engaged']['total'],
                'total_interactions_28d'   => $parsed['total_interactions']['total'],
                'reach_28d'                => $parsed['reach']['total'],
                'profile_views_28d'        => $parsed['profile_views']['total'],
                'follower_count_delta_28d' => $parsed['follower_count']['delta'],
                'reach_series'             => $parsed['reach']['series'],
                'accounts_engaged_series'  => $parsed['accounts_engaged']['series'],
                'audience_gender_age'      => $this->parseDemographicBreakdown($genderAge, ['age', 'gender']),
                'audience_country'         => $this->parseDemographicBreakdown($country, ['country']),
                'audience_city'            => $this->parseDemographicBreakdown($city, ['city']),
            ]
        );
    }

    // -------------------------------------------------------------------------
    // API calls
    // -------------------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     *
     * @throws RequestException
     */
    private function fetchPerformanceMetrics(string $instagramId, string $token): array
    {
        $since = Carbon::now()->subDays(28)->startOfDay()->timestamp;
        $until = Carbon::now()->endOfDay()->timestamp;

        $response = Http::timeout(15)
            ->get(self::API_BASE . "/{$instagramId}/insights", [
                'metric'       => self::PERFORMANCE_METRICS,
                'period'       => 'day',
                'since'        => $since,
                'until'        => $until,
                'access_token' => $token,
            ])
            ->throw()
            ->json();

        return $response['data'] ?? [];
    }

    /**
     * Try demographic metrics in order of preference, returning the first
     * that succeeds. Errors like 3006 "Not enough users" are caught per-metric
     * so a failure on one does not abort the others.
     *
     * Priority:
     *   1. follower_demographics  — based on all followers (largest sample)
     *   2. reached_audience_demographics — based on reached accounts
     *   3. engaged_audience_demographics — based on engaged accounts (smallest sample)
     *
     * @return array<string, mixed>  the first item from `data[]`, or []
     */
    private function fetchDemographicBreakdownSafe(string $instagramId, string $token, string $breakdown): array
    {
        $metrics = [
            'follower_demographics',
            'reached_audience_demographics',
            'engaged_audience_demographics',
        ];

        foreach ($metrics as $metric) {
            try {
                $result = $this->fetchDemographicBreakdown($instagramId, $token, $metric, $breakdown);

                if (! empty($result)) {
                    return $result;
                }
            } catch (RequestException $e) {
                Log::debug("Instagram demographics [{$metric}] skipped for breakdown [{$breakdown}]", [
                    'code'    => $e->response?->json('error.code'),
                    'message' => $e->response?->json('error.message'),
                ]);
            }
        }

        return [];
    }

    /**
     * @return array<string, mixed>  the first item from `data[]`, or []
     *
     * @throws RequestException
     */
    private function fetchDemographicBreakdown(string $instagramId, string $token, string $metric, string $breakdown): array
    {
        $response = Http::timeout(15)
            ->get(self::API_BASE . "/{$instagramId}/insights", [
                'metric'       => $metric,
                'period'       => 'lifetime',
                'metric_type'  => 'total_value',
                'breakdown'    => $breakdown,
                'access_token' => $token,
            ])
            ->throw()
            ->json();

        return $response['data'][0] ?? [];
    }

    // -------------------------------------------------------------------------
    // Parsing
    // -------------------------------------------------------------------------

    /**
     * Parse daily performance metrics into totals + series.
     *
     * Each metric in `data[]`:
     *   { "name": "reach", "period": "day",
     *     "values": [{"value": N, "end_time": "ISO8601"}, ...] }
     *
     * Metrics not returned by the API (e.g. profile_views on Creator accounts)
     * stay at their zero defaults.
     *
     * @param  list<array<string, mixed>>  $data
     * @return array<string, array{total: int, delta: int, series: list<array{date: string, value: int}>}>
     */
    private function parsePerformanceMetrics(array $data): array
    {
        $result = [
            'reach'               => ['total' => 0, 'delta' => 0, 'series' => []],
            'follower_count'      => ['total' => 0, 'delta' => 0, 'series' => []],
            'accounts_engaged'    => ['total' => 0, 'delta' => 0, 'series' => []],
            'total_interactions'  => ['total' => 0, 'delta' => 0, 'series' => []],
            'profile_views'       => ['total' => 0, 'delta' => 0, 'series' => []],
        ];

        foreach ($data as $metric) {
            $name = $metric['name'] ?? '';

            if (! array_key_exists($name, $result)) {
                continue;
            }

            $values = $metric['values'] ?? [];
            $series = [];
            $total  = 0;

            foreach ($values as $point) {
                $value = (int) ($point['value'] ?? 0);
                $date  = isset($point['end_time'])
                    ? Carbon::parse($point['end_time'])->toDateString()
                    : null;

                if ($date) {
                    $series[] = ['date' => $date, 'value' => $value];
                }

                $total += $value;
            }

            $result[$name]['total']  = $total;
            $result[$name]['series'] = $series;

            // follower_count: delta = last day - first day (net gain/loss)
            if ($name === 'follower_count' && count($values) >= 2) {
                $result[$name]['delta'] = (int) ($values[array_key_last($values)]['value'] ?? 0)
                    - (int) ($values[0]['value'] ?? 0);
            }
        }

        return $result;
    }

    /**
     * Parse a `follower_demographics` response into a flat key → count array.
     *
     * API response (v23.0):
     *   {
     *     "total_value": {
     *       "breakdowns": [{
     *         "dimension_keys": ["age", "gender"],
     *         "results": [
     *           {"dimension_values": ["25-34", "F"], "value": 200},
     *           ...
     *         ]
     *       }]
     *     }
     *   }
     *
     * Single-key dimension (country/city) → key = dimension_values[0]
     * Multi-key dimension (age + gender)  → key = "25-34_F"
     *
     * Uses a set comparison for dimension_keys to be resilient to ordering.
     *
     * @param  array<string, mixed>  $data
     * @param  list<string>          $expectedKeys
     * @return array<string, int>|null
     */
    private function parseDemographicBreakdown(array $data, array $expectedKeys): ?array
    {
        $breakdowns = $data['total_value']['breakdowns'] ?? [];
        $expectedSorted = $expectedKeys;
        sort($expectedSorted);

        foreach ($breakdowns as $breakdown) {
            $keys = $breakdown['dimension_keys'] ?? [];
            sort($keys);

            if ($keys !== $expectedSorted) {
                continue;
            }

            $parsed = [];

            foreach ($breakdown['results'] ?? [] as $result) {
                $dims  = $result['dimension_values'] ?? [];
                $value = (int) ($result['value'] ?? 0);
                $key   = count($dims) === 1 ? $dims[0] : implode('_', $dims);

                $parsed[$key] = $value;
            }

            if (! empty($parsed)) {
                arsort($parsed);

                return $parsed;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Error detection
    // -------------------------------------------------------------------------

    /**
     * Instagram returns HTTP 400 with OAuthException code 10 or 200 when the
     * app lacks the required permission.
     */
    private function isPermissionError(RequestException $e): bool
    {
        if ($e->response?->status() !== 400) {
            return false;
        }

        $code = $e->response?->json('error.code');

        // Code 10: permission denied; Code 200: API permission error
        return in_array($code, [10, 200], strict: true);
    }
}
