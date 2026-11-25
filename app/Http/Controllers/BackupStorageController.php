<?php

namespace App\Http\Controllers;

use App\Services\BackupStorageUsageService;
use Illuminate\Http\JsonResponse;

class BackupStorageController extends Controller
{
    public function __invoke(BackupStorageUsageService $usage): JsonResponse
    {
        return response()->json($usage->snapshot(fresh: true), 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
