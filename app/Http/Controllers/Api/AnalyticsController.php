<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * AnalyticsController — dashboard KPI & chart data endpoints.
 */
class AnalyticsController extends Controller
{
    /**
     * GET /api/analytics/overview
     * Returns high-level counts for the dashboard.
     */
    public function overview(): JsonResponse
    {
        $today = today();

        $stats = [
            'total_complaints'      => Complaint::count(),
            'today_complaints'      => Complaint::whereDate('created_at', $today)->count(),
            'pending_review'        => Complaint::where('requires_human_review', true)
                                                ->whereNotIn('status', ['resolved', 'rejected'])
                                                ->count(),
            'in_progress'           => Complaint::whereIn('status', ['routed', 'assigned', 'in_progress'])->count(),
            'resolved_today'        => Complaint::where('status', 'resolved')
                                                ->whereDate('resolved_at', $today)
                                                ->count(),
            'sla_breached'          => Complaint::whereNotNull('sla_deadline')
                                                ->where('sla_deadline', '<', now())
                                                ->whereNotIn('status', ['resolved', 'rejected'])
                                                ->count(),
            'avg_ai_confidence'     => round(Complaint::whereNotNull('ai_confidence')->avg('ai_confidence') * 100, 1),
            'human_review_rate'     => Complaint::count() > 0
                ? round(Complaint::where('requires_human_review', true)->count() / Complaint::count() * 100, 1)
                : 0,
        ];

        return response()->json($stats);
    }

    /**
     * GET /api/analytics/by-category
     */
    public function byCategory(): JsonResponse
    {
        $data = Complaint::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();

        return response()->json($data);
    }

    /**
     * GET /api/analytics/by-agency
     */
    public function byAgency(): JsonResponse
    {
        $data = Agency::withCount([
            'complaints',
            'complaints as resolved_count' => fn ($q) => $q->where('status', 'resolved'),
            'complaints as pending_count'  => fn ($q) => $q->whereNotIn('status', ['resolved', 'rejected']),
        ])->get();

        return response()->json($data);
    }

    /**
     * GET /api/analytics/timeline?days=7
     */
    public function timeline(): JsonResponse
    {
        $days = min((int) request('days', 7), 30);

        $data = Complaint::select(
                DB::raw("date(created_at) as date"),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved"),
                DB::raw("SUM(CASE WHEN requires_human_review = 1 THEN 1 ELSE 0 END) as requires_review"),
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy(DB::raw('date(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    /**
     * GET /api/analytics/ai-performance
     */
    public function aiPerformance(): JsonResponse
    {
        $total      = Complaint::count();
        $withAi     = Complaint::whereNotNull('ai_confidence')->count();
        $highConf   = Complaint::where('ai_confidence', '>=', 0.80)->count();
        $medConf    = Complaint::whereBetween('ai_confidence', [0.70, 0.80])->count();
        $lowConf    = Complaint::where('ai_confidence', '<', 0.70)->count();
        $corrected  = Complaint::whereNotNull('corrected_category')->count();

        return response()->json([
            'total_complaints'       => $total,
            'ai_analyzed'            => $withAi,
            'high_confidence'        => $highConf,
            'medium_confidence'      => $medConf,
            'low_confidence'         => $lowConf,
            'human_corrections'      => $corrected,
            'correction_rate'        => $withAi > 0 ? round($corrected / $withAi * 100, 1) : 0,
            'avg_confidence_pct'     => round(Complaint::whereNotNull('ai_confidence')->avg('ai_confidence') * 100, 1),
            'channels'               => Complaint::select('channel', DB::raw('count(*) as count'))
                                                  ->groupBy('channel')->get(),
        ]);
    }
}
