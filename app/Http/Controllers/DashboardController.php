<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStatistics()
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $endMonth = $currentMonth->copy()->endOfMonth();
        
        $totalTickets = Ticket::whereBetween('created_at',[$currentMonth,$endMonth])->count();

        $activeTickets = Ticket::whereBetween('created_at',[$currentMonth,$endMonth])
            ->where('status', '!=','resolved')->count();
        $resolvedTicket = Ticket::whereBetween('created_at',[$currentMonth,$endMonth])
            ->where('status','resolved')->count();
        $avgResolutionTime = Ticket::whereBetween('created_at',[$currentMonth,$endMonth])
            ->where('status','resolved')
            ->whereNotNull('completed_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR,created_at,completed_at)) as avg_time'))
            ->value('avg_time') ?? 0;

        $statusDistribution = [
            'open'=>Ticket::whereBetween('created_at',[$currentMonth,$endMonth])->where('status','open')->count(),
            'in_progress' => Ticket::whereBetween('created_at',[$currentMonth,$endMonth])->where('status','in_progress')->count(),
            'resolved' => Ticket::whereBetween('created_at',[$currentMonth,$endMonth])->where('status','resolved')->count(),
            'rejected' => Ticket::whereBetween('created_at',[$currentMonth,$endMonth])->where('status','rejected')->count(),

        ];

        $dashboardData = [
            'total_tickets' => $totalTickets,
            'active_tickets' => $activeTickets,
            'resolved_tickets' => $resolvedTicket,
            'avg_resolution_time' => $avgResolutionTime,
            'status_distribution' => $statusDistribution,
        ];

        return response()->json([
            'message' => 'Dashboard statistics fetched successfully',
            'data' => new DashboardResource($dashboardData)
        ],200);
    }
}
