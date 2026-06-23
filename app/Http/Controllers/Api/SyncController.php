<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\SyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SyncController extends Controller
{
    /**
     * Receive attendance records pushed from the local ZKTeco sync agent.
     *
     * Expected payload:
     * {
     *   "records": [
     *     { "emp_code": "001", "punch_time": "2026-06-22 08:00:00", "punch_state": 0, "verify_type": 0 },
     *     ...
     *   ]
     * }
     */
    public function attendance(Request $request): JsonResponse
    {
        $request->validate([
            'records'                => 'required|array|min:1',
            'records.*.emp_code'     => 'required|string',
            'records.*.punch_time'   => 'required|date',
            'records.*.punch_state'  => 'nullable|integer',
            'records.*.verify_type'  => 'nullable|integer',
            'records.*.terminal_sn'  => 'nullable|string',
        ]);

        $syncLog = SyncLog::create([
            'type'       => 'attendance',
            'status'     => 'running',
            'started_at' => now(),
        ]);

        // Pre-load all referenced employees in one query to avoid N+1
        $empCodes = collect($request->records)->pluck('emp_code')->unique()->values()->all();
        $empMap   = Employee::whereIn('emp_code', $empCodes)->pluck('id', 'emp_code');

        $count = 0;

        foreach ($request->records as $record) {
            $empCode     = $record['emp_code'];
            $punchCarbon = Carbon::parse($record['punch_time']);

            AttendanceLog::updateOrCreate(
                ['emp_code' => $empCode, 'punch_time' => $punchCarbon],
                [
                    'employee_id'  => $empMap[$empCode] ?? null,
                    'punch_date'   => $punchCarbon->toDateString(),
                    'punch_state'  => $record['punch_state'] ?? 0,
                    'verify_type'  => $record['verify_type'] ?? 0,
                    'terminal_sn'  => $record['terminal_sn'] ?? null,
                    'is_processed' => false,
                ]
            );

            $count++;
        }

        $syncLog->update([
            'status'          => 'success',
            'records_fetched' => $count,
            'completed_at'    => now(),
        ]);

        return response()->json(['synced' => $count]);
    }

    /**
     * Receive employee records pushed from the local ZKTeco sync agent.
     *
     * Expected payload:
     * {
     *   "employees": [
     *     { "emp_code": "001", "first_name": "Juan", "last_name": "Dela Cruz" },
     *     ...
     *   ]
     * }
     */
    public function employees(Request $request): JsonResponse
    {
        $request->validate([
            'employees'                => 'required|array|min:1',
            'employees.*.emp_code'     => 'required|string',
            'employees.*.first_name'   => 'nullable|string',
            'employees.*.last_name'    => 'nullable|string',
        ]);

        $syncLog = SyncLog::create([
            'type'       => 'employees',
            'status'     => 'running',
            'started_at' => now(),
        ]);

        $count = 0;

        foreach ($request->employees as $emp) {
            Employee::updateOrCreate(
                ['emp_code' => $emp['emp_code']],
                [
                    'first_name' => $emp['first_name'] ?? '',
                    'last_name'  => $emp['last_name']  ?? '',
                    'is_active'  => true,
                    'synced_at'  => now(),
                ]
            );
            $count++;
        }

        $syncLog->update([
            'status'          => 'success',
            'records_fetched' => $count,
            'completed_at'    => now(),
        ]);

        return response()->json(['synced' => $count]);
    }
}
