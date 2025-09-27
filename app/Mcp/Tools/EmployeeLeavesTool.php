<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Models\Leave;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class EmployeeLeavesTool extends BaseTool
{
    /**
     * The tool's name.
     */
    public function name(): string
    {
        return 'employee_leaves';
    }

    /**
     * The tool's description.
     */
    public function description(): string
    {
        return 'Get leave records for a specific employee. Can filter by date range, leave type, and status. Returns detailed leave information including approval status and comments.';
    }

    /**
     * The tool's input schema.
     */
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'employee_id' => [
                    'type' => 'integer',
                    'description' => 'The ID of the employee to get leave records for',
                ],
                'employee_name' => [
                    'type' => 'string',
                    'description' => 'The name of the employee to get leave records for (alternative to employee_id)',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Filter by leave status (pending, approved, rejected)',
                    'enum' => ['pending', 'approved', 'rejected', 'cancelled'],
                ],
                'leave_type' => [
                    'type' => 'string',
                    'description' => 'Filter by leave type (vacation, sick, personal, etc.)',
                ],
                'start_date' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Start date for filtering leave records (YYYY-MM-DD)',
                ],
                'end_date' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'End date for filtering leave records (YYYY-MM-DD)',
                ],
                'year' => [
                    'type' => 'integer',
                    'description' => 'Filter by specific year (e.g., 2024)',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results to return (default: 20)',
                    'default' => 20,
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ],
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        try {
            $employeeId = $request->get('employee_id');
            $employeeName = $request->get('employee_name');
            $status = $request->get('status');
            $leaveType = $request->get('leave_type');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $year = $request->get('year');
            $limit = $request->get('limit', 20);

            // Find employee if name is provided instead of ID
            if ($employeeName && !$employeeId) {
                $employee = User::where('name', 'like', "%{$employeeName}%")->first();
                if (!$employee) {
                    return Response::error("Employee with name '{$employeeName}' not found");
                }
                $employeeId = $employee->id;
            }

            // Validate employee ID
            if (!$employeeId) {
                return Response::error('Either employee_id or employee_name must be provided');
            }

            // Verify employee exists
            $employee = User::find($employeeId);
            if (!$employee) {
                return Response::error("Employee with ID {$employeeId} not found");
            }

            // Build the query
            $query = Leave::with(['user', 'approvedBy', 'appliedBy'])
                ->byUser($employeeId);

            // Apply filters
            if ($status) {
                $query->byStatus($status);
            }

            if ($leaveType) {
                $query->byType($leaveType);
            }

            if ($startDate && $endDate) {
                $query->byDateRange($startDate, $endDate);
            } elseif ($year) {
                $query->whereYear('date', $year);
            }

            // Order by date (most recent first)
            $query->orderBy('date', 'desc');

            // Execute the query with limit
            $leaves = $query->limit($limit)->get();

            // Format the results
            $results = $leaves->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'date' => $leave->date->format('Y-m-d'),
                    'type' => $leave->type,
                    'category' => $leave->category,
                    'reason' => $leave->reason,
                    'status' => $leave->status,
                    'applied_by' => $leave->appliedBy ? [
                        'id' => $leave->appliedBy->id,
                        'name' => $leave->appliedBy->name,
                        'email' => $leave->appliedBy->email,
                    ] : null,
                    'approved_by' => $leave->approvedBy ? [
                        'id' => $leave->approvedBy->id,
                        'name' => $leave->approvedBy->name,
                        'email' => $leave->approvedBy->email,
                    ] : null,
                    'reject_reason' => $leave->reject_reason,
                    'history' => $leave->history,
                    'created_at' => $leave->created_at->format('Y-m-d H:i:s'),
                ];
            });

            // Calculate summary statistics
            $summary = [
                'total_leaves' => $leaves->count(),
                'by_status' => $leaves->groupBy('status')->map->count(),
                'by_type' => $leaves->groupBy('type')->map->count(),
                'by_category' => $leaves->groupBy('category')->map->count(),
            ];

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Retrieved {$results->count()} leave record(s) for {$employee->name}",
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'employee_id' => $employee->employee_id,
                    'team' => $employee->team ? $employee->team->name : null,
                    'designation' => $employee->designation ? $employee->designation->name : null,
                ],
                'summary' => $summary,
                'leaves' => $results->toArray(),
            ];

            return Response::json(json_encode($responseData));

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve employee leave records: ' . $e->getMessage());
        }
    }
}
