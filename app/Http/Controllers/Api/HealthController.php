<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('SELECT 1');
            $database = true;
        } catch (\Throwable) {
            $database = false;
        }

        $status = $database ? 'ok' : 'error';
        $httpCode = $database ? 200 : 503;

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toISOString(),
            'database' => $database,
        ], $httpCode);
    }
}
