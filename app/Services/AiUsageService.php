<?php

namespace App\Services;

use App\Models\AiInteraction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AiUsageService
{
    /**
     * Get AI usage statistics for a given period
     */
    public function getAiUsageStats($period = 'month', $startDate = null, $endDate = null)
    {
        $query = AiInteraction::query();

        // Set date range based on period
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } else {
            switch ($period) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', now()->startOfWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->startOfMonth());
                    break;
                case 'year':
                    $query->where('created_at', '>=', now()->startOfYear());
                    break;
            }
        }

        $totalQueries = $query->count();
        $uniqueSessions = $query->clone()->distinct('session_id')->count();
        $uniqueUsers = $query->clone()->whereNotNull('user_id')->distinct('user_id')->count();
        $anonymousQueries = $query->clone()->whereNull('user_id')->count();

        // Calculate average queries per session
        $avgQueriesPerSession = $uniqueSessions > 0 ? round($totalQueries / $uniqueSessions, 2) : 0;

        // Get response quality metrics (assuming longer responses are better)
        $avgResponseLength = $query->clone()->selectRaw('AVG(LENGTH(response)) as avg_length')->value('avg_length') ?? 0;

        return [
            'total_queries' => $totalQueries,
            'unique_sessions' => $uniqueSessions,
            'unique_users' => $uniqueUsers,
            'anonymous_queries' => $anonymousQueries,
            'avg_queries_per_session' => $avgQueriesPerSession,
            'avg_response_length' => round($avgResponseLength, 0),
            'period' => $period,
            'start_date' => $startDate ?? $this->getPeriodStartDate($period),
            'end_date' => $endDate ?? now()
        ];
    }

    /**
     * Get AI usage for today
     */
    public function getTodayAiUsage()
    {
        return $this->getAiUsageStats('today');
    }

    /**
     * Get AI usage for current month
     */
    public function getCurrentMonthAiUsage()
    {
        return $this->getAiUsageStats('month');
    }

    /**
     * Get AI usage by query type (based on query content analysis)
     */
    public function getAiUsageByQueryType($period = 'month')
    {
        $query = AiInteraction::query();

        // Set date range
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->startOfMonth());
                break;
            case 'year':
                $query->where('created_at', '>=', now()->startOfYear());
                break;
        }

        return $query->select(
                DB::raw('CASE 
                    WHEN LOWER(query) LIKE "%loan%" OR LOWER(query) LIKE "%borrow%" THEN "Loan Queries"
                    WHEN LOWER(query) LIKE "%savings%" OR LOWER(query) LIKE "%deposit%" THEN "Savings Queries"
                    WHEN LOWER(query) LIKE "%payment%" OR LOWER(query) LIKE "%pay%" THEN "Payment Queries"
                    WHEN LOWER(query) LIKE "%account%" OR LOWER(query) LIKE "%balance%" THEN "Account Queries"
                    WHEN LOWER(query) LIKE "%help%" OR LOWER(query) LIKE "%how%" THEN "Help Queries"
                    ELSE "General Queries"
                END as query_type'),
                DB::raw('count(*) as count'),
                DB::raw('count(DISTINCT session_id) as unique_sessions')
            )
            ->groupBy(DB::raw('CASE 
                WHEN LOWER(query) LIKE "%loan%" OR LOWER(query) LIKE "%borrow%" THEN "Loan Queries"
                WHEN LOWER(query) LIKE "%savings%" OR LOWER(query) LIKE "%deposit%" THEN "Savings Queries"
                WHEN LOWER(query) LIKE "%payment%" OR LOWER(query) LIKE "%pay%" THEN "Payment Queries"
                WHEN LOWER(query) LIKE "%account%" OR LOWER(query) LIKE "%balance%" THEN "Account Queries"
                WHEN LOWER(query) LIKE "%help%" OR LOWER(query) LIKE "%how%" THEN "Help Queries"
                ELSE "General Queries"
            END'))
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->query_type => [
                        'queries' => $item->count,
                        'unique_sessions' => $item->unique_sessions
                    ]
                ];
            });
    }

    /**
     * Calculate AI costs based on usage
     */
    public function calculateAiCosts($queryCount, $costPerQuery = 10)
    {
        return $queryCount * $costPerQuery;
    }

    /**
     * Get AI service billing information
     */
    public function getAiBillingInfo($period = 'month')
    {
        $usage = $this->getAiUsageStats($period);
        $basePrice = 250000; // Base monthly price for AI service
        $costPerQuery = 10; // Cost per query in TZS
        $includedQueries = 10000; // Included queries in base price
        
        $totalQueries = $usage['total_queries'];
        $extraQueries = max(0, $totalQueries - $includedQueries);
        $extraCost = $this->calculateAiCosts($extraQueries, $costPerQuery);
        $totalCost = $basePrice + $extraCost;

        return [
            'base_price' => $basePrice,
            'included_queries' => $includedQueries,
            'used_queries' => $totalQueries,
            'extra_queries' => $extraQueries,
            'cost_per_query' => $costPerQuery,
            'extra_cost' => $extraCost,
            'total_cost' => $totalCost,
            'usage_percentage' => $includedQueries > 0 ? round(($totalQueries / $includedQueries) * 100, 2) : 0,
            'unique_sessions' => $usage['unique_sessions'],
            'unique_users' => $usage['unique_users'],
            'avg_queries_per_session' => $usage['avg_queries_per_session'],
            'period' => $period
        ];
    }

    /**
     * Get period start date
     */
    private function getPeriodStartDate($period)
    {
        switch ($period) {
            case 'today':
                return now()->startOfDay();
            case 'week':
                return now()->startOfWeek();
            case 'month':
                return now()->startOfMonth();
            case 'year':
                return now()->startOfYear();
            default:
                return now()->startOfMonth();
        }
    }

    /**
     * Get AI service health
     */
    public function getAiServiceHealth()
    {
        $lastWeek = $this->getAiUsageStats('week');
        $avgQueriesPerSession = $lastWeek['avg_queries_per_session'];
        
        // Calculate health score based on engagement (more queries per session = better engagement)
        $engagementScore = min(100, $avgQueriesPerSession * 20); // Scale to 100
        $usageScore = min(100, ($lastWeek['total_queries'] / 100) * 10); // Scale based on total usage
        
        $healthScore = ($engagementScore + $usageScore) / 2;

        return [
            'health_score' => round($healthScore, 1),
            'status' => $healthScore >= 80 ? 'excellent' : ($healthScore >= 60 ? 'good' : ($healthScore >= 40 ? 'fair' : 'poor')),
            'engagement_score' => round($engagementScore, 1),
            'usage_score' => round($usageScore, 1),
            'avg_queries_per_session' => $avgQueriesPerSession,
            'last_updated' => now()
        ];
    }

    /**
     * Get AI usage trends for the last N days
     */
    public function getAiUsageTrends($days = 30)
    {
        $trends = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayStats = $this->getAiUsageStats('day', $date->startOfDay(), $date->endOfDay());
            
            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'total_queries' => $dayStats['total_queries'],
                'unique_sessions' => $dayStats['unique_sessions'],
                'unique_users' => $dayStats['unique_users']
            ];
        }

        return $trends;
    }

    /**
     * Get popular AI queries (most common query patterns)
     */
    public function getPopularQueries($period = 'month', $limit = 10)
    {
        $query = AiInteraction::query();

        // Set date range
        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->startOfMonth());
                break;
            case 'year':
                $query->where('created_at', '>=', now()->startOfYear());
                break;
        }

        return $query->select(
                DB::raw('SUBSTRING(query, 1, 50) as query_preview'),
                DB::raw('count(*) as frequency')
            )
            ->groupBy(DB::raw('SUBSTRING(query, 1, 50)'))
            ->orderBy('frequency', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'query' => $item->query_preview . '...',
                    'frequency' => $item->frequency
                ];
            });
    }
}
