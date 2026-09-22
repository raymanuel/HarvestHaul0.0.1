<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditController extends Controller
{
    public function auditLogs(Request $request)
    {
        $query = AuditLog::with(['admin'])->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }
        if ($request->filled('target_type')) {
            $query->where('target_type', $request->query('target_type'));
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->query('from').' 00:00:00');
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->query('to').' 23:59:59');
        }

        $logs = $query->paginate(20)->withQueryString();

        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $targetTypes = AuditLog::select('target_type')->distinct()->orderBy('target_type')->pluck('target_type');

        return view('admin.audit-logs', compact('logs', 'actions', 'targetTypes'));
    }
}
