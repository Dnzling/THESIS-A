<?php

namespace App\Http\Controllers\Api\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierPerformanceMetric;
use Illuminate\Support\Facades\DB;

class SupplierPerformanceController extends Controller
{
    public function getPerformanceMetrics($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            $metrics = [
                'total_orders' => $supplier->total_orders,
                'on_time_deliveries' => $supplier->on_time_deliveries,
                'late_deliveries' => $supplier->late_deliveries,
                'on_time_percentage' => $supplier->total_orders > 0 
                    ? round(($supplier->on_time_deliveries / $supplier->total_orders) * 100, 2)
                    : 0,
                'quality_score' => $supplier->quality_score,
                'average_delivery_days' => $supplier->average_delivery_days,
                'recent_delay_percentage' => $supplier->recent_delay_percentage,
                'rating' => $supplier->rating,
                'risk_score' => $this->calculateRiskScore($supplier)
            ];

            return response()->json($metrics);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found'
            ], 404);
        }
    }

    public function getPerformanceHistory($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);

            // Get last 12 months of metrics
            $history = SupplierPerformanceMetric::where('supplier_id', $id)
                ->orderBy('metric_date', 'desc')
                ->limit(12)
                ->get()
                ->reverse()
                ->values();

            if ($history->isEmpty()) {
                // Return current month data if no history
                $history = collect([[
                    'metric_date' => now()->format('Y-m-d'),
                    'on_time_count' => $supplier->on_time_deliveries,
                    'late_count' => $supplier->late_deliveries,
                    'quality_score' => $supplier->quality_score,
                    'average_delivery_days' => $supplier->average_delivery_days
                ]]);
            }

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve performance history'
            ], 500);
        }
    }

    public function calculateRiskScore($supplier)
    {
        // Risk scoring formula (0-100)
        $lateDeliveryPercentage = $supplier->total_orders > 0 
            ? ($supplier->late_deliveries / $supplier->total_orders) * 100
            : 0;
        
        $paymentDelayRisk = $supplier->recent_delay_percentage;
        $qualityRisk = max(0, 100 - ($supplier->quality_score * 20));
        $ratingRisk = max(0, 100 - ($supplier->rating * 20));

        $riskScore = ($lateDeliveryPercentage * 0.40) + 
                     ($paymentDelayRisk * 0.30) + 
                     ($qualityRisk * 0.15) + 
                     ($ratingRisk * 0.15);

        return round(min(100, $riskScore), 2);
    }

    public function getRiskLevel($riskScore)
    {
        if ($riskScore < 20) {
            return ['level' => 'low', 'color' => 'success', 'severity' => 'Low'];
        } elseif ($riskScore < 50) {
            return ['level' => 'medium', 'color' => 'warning', 'severity' => 'Medium'];
        } elseif ($riskScore < 75) {
            return ['level' => 'high', 'color' => 'danger', 'severity' => 'High'];
        } else {
            return ['level' => 'critical', 'color' => 'danger', 'severity' => 'Critical'];
        }
    }

    public function getAtRiskSuppliers()
    {
        try {
            $suppliers = Supplier::where('status', '!=', 'blacklisted')
                ->get()
                ->map(function ($supplier) {
                    $riskScore = $this->calculateRiskScore($supplier);
                    $riskInfo = $this->getRiskLevel($riskScore);
                    
                    return [
                        'id' => $supplier->id,
                        'supplier_name' => $supplier->supplier_name,
                        'company_name' => $supplier->company_name,
                        'risk_score' => $riskScore,
                        'risk_level' => $riskInfo['level'],
                        'risk_color' => $riskInfo['color'],
                        'risk_reasons' => $this->getRiskReasons($supplier),
                        'status' => $supplier->status
                    ];
                })
                ->filter(function ($supplier) {
                    return $supplier['risk_score'] >= 50;
                })
                ->sortByDesc('risk_score')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $suppliers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve at-risk suppliers'
            ], 500);
        }
    }

    public function getRiskReasons($supplier)
    {
        $reasons = [];

        $lateDeliveryPercentage = $supplier->total_orders > 0 
            ? ($supplier->late_deliveries / $supplier->total_orders) * 100
            : 0;

        if ($lateDeliveryPercentage > 20) {
            $reasons[] = "High late delivery rate: {$lateDeliveryPercentage}%";
        }

        if ($supplier->recent_delay_percentage > 10) {
            $reasons[] = "Recent payment delays: {$supplier->recent_delay_percentage}%";
        }

        if ($supplier->quality_score < 3) {
            $reasons[] = "Low quality score: {$supplier->quality_score}";
        }

        if ($supplier->rating < 2) {
            $reasons[] = "Low supplier rating: {$supplier->rating}";
        }

        if ($supplier->status === 'blacklisted') {
            $reasons[] = "Supplier is blacklisted";
        }

        return $reasons;
    }

    public function getTopPerformers($limit = 5)
    {
        try {
            $suppliers = Supplier::where('status', 'active')
                ->orderBy('quality_score', 'desc')
                ->orderBy('on_time_deliveries', 'desc')
                ->limit($limit)
                ->get(['id', 'supplier_name', 'company_name', 'quality_score', 'rating', 'on_time_deliveries', 'total_orders']);

            return response()->json([
                'success' => true,
                'data' => $suppliers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve top performers'
            ], 500);
        }
    }
}
