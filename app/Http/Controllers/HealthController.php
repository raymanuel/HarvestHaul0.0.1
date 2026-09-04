<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            DB::table('users')->count();
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'app' => 'up',
                'database' => false,
                'checked_at' => now()->toIso8601String(),
            ], 503);
        }

        return response()->json([
            'status' => 'ok',
            'app' => 'up',
            'database' => true,
            'checked_at' => now()->toIso8601String(),
        ]);
    }
}
