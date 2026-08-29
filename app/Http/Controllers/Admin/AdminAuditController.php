<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;

class AdminAuditController extends Controller
{
    public function auditLogs()
    {
        $logs = AuditLog::with(['admin'])->latest()->paginate(20);

        return view('admin.audit-logs', compact('logs'));
    }
}
