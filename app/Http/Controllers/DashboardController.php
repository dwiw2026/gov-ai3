<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Complaint;
use Illuminate\Http\Request;

/**
 * DashboardController — serves the government operator dashboard views.
 */
class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function complaints(Request $request)
    {
        $query = Complaint::with(['agency', 'officer'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('agency_id')) {
            $query->where('agency_id', $request->agency_id);
        }

        $complaints = $query->paginate(20)->withQueryString();
        $agencies   = Agency::where('is_active', true)->get();

        return view('dashboard.complaints', compact('complaints', 'agencies'));
    }

    public function complaintDetail(string $ticket)
    {
        $complaint = Complaint::where('ticket_number', $ticket)
            ->with(['agency', 'officer', 'auditLogs'])
            ->firstOrFail();

        return view('dashboard.complaint-detail', compact('complaint'));
    }

    public function analytics()
    {
        return view('dashboard.analytics');
    }
}
