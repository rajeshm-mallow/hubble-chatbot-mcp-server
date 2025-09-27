<?php

namespace App\Mcp\Tools;

use App\Models\Leave;
use App\Models\User;
use App\Models\Team;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class LeaveSummaryTool extends BaseTool
{
    /**
     * The tool's name.
     */
    public function name(): string
    {
        return 'leave_summary';
    }

    /**
     * The tool's description.
     */
    public function description(): string
    {
        return 'Get a comprehensive summary of leave records across the organization. Can filter by department, date range, and status. Provides analytics and trends.';
    }

    /**
     * The tool's input schema.
     */
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'department' => [
                    'type' => 'string',
                    'description' => 'Filter by department',
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
                'status' => [
                    'type' => 'string',
                    'description' => 'Filter by leave status',
                    'enum' => ['pending', 'approved', 'rejected', 'cancelled'],
                ],
                'leave_type' => [
                    'type' => 'string',
                    'description' => 'Filter by leave type',
                ],
                'include_employee_details' => [
                    'type' => 'boolean',
                    'description' => 'Whether to include detailed employee information (default: false)',
                    'default' => false,
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
            $department = $request->get('department');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $year = $request->get('year');
            $status = $request->get('status');
            $leaveType = $request->get('leave_type');
            $includeEmployeeDetails = $request->get('include_employee_details', false);

            // Build the base query
            $query = Leave::with(['user', 'approvedBy', 'appliedBy']);

            // Apply filters
            if ($department) {
                $query->whereHas('user', function ($q) use ($department) {
                    $q->whereHas('team', function ($subQ) use ($department) {
                        $subQ->where('name', 'like', "%{$department}%");
                    });
                });
            }

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

            // Execute the query
            $leaves = $query->get();

            // Calculate comprehensive statistics
            $totalLeaves = $leaves->count();
            $stats = [
                'total_leaves' => $totalLeaves,
                'by_status' => $leaves->groupBy('status')->map(function ($group) use ($totalLeaves) {
                    return [
                        'count' => $group->count(),
                        'percentage' => $totalLeaves > 0 ? round(($group->count() / $totalLeaves) * 100, 2) : 0,
                    ];
                }),
                'by_type' => $leaves->groupBy('type')->map(function ($group) use ($totalLeaves) {
                    return [
                        'count' => $group->count(),
                        'percentage' => $totalLeaves > 0 ? round(($group->count() / $totalLeaves) * 100, 2) : 0,
                    ];
                }),
                'by_category' => $leaves->groupBy('category')->map(function ($group) use ($totalLeaves) {
                    return [
                        'count' => $group->count(),
                        'percentage' => $totalLeaves > 0 ? round(($group->count() / $totalLeaves) * 100, 2) : 0,
                    ];
                }),
                'by_department' => $leaves->groupBy(function ($leave) {
                    return $leave->user && $leave->user->team ? $leave->user->team->name : 'Unknown';
                })->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'employees' => $group->pluck('user_id')->unique()->count(),
                    ];
                }),
                'by_month' => $leaves->groupBy(function ($leave) {
                    return $leave->date->format('Y-m');
                })->map(function ($group) {
                    return [
                        'count' => $group->count(),
                    ];
                }),
            ];

            // Get top employees by leave count
            $topEmployeesByLeave = $leaves->groupBy('user_id')
                ->map(function ($group) {
                    $user = $group->first()->user;
                    return [
                        'user_id' => $group->first()->user_id,
                        'employee_name' => $user ? $user->name : 'Unknown',
                        'employee_id' => $user ? $user->employee_id : null,
                        'department' => $user && $user->team ? $user->team->name : 'Unknown',
                        'leave_count' => $group->count(),
                    ];
                })
                ->sortByDesc('leave_count')
                ->take(10)
                ->values();

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Leave summary generated for {$leaves->count()} leave record(s)",
                'filters_applied' => array_filter([
                    'department' => $department,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'year' => $year,
                    'status' => $status,
                    'leave_type' => $leaveType,
                ]),
                'statistics' => $stats,
                'top_employees_by_leave' => $topEmployeesByLeave,
            ];

            // Include detailed employee information if requested
            if ($includeEmployeeDetails) {
                $responseData['employees'] = $leaves->groupBy('user_id')->map(function ($group) {
                    $user = $group->first()->user;
                    return [
                        'id' => $user ? $user->id : null,
                        'name' => $user ? $user->name : 'Unknown',
                        'email' => $user ? $user->email : null,
                        'employee_id' => $user ? $user->employee_id : null,
                        'department' => $user && $user->team ? $user->team->name : 'Unknown',
                        'designation' => $user && $user->designation ? $user->designation->name : 'Unknown',
                        'leave_count' => $group->count(),
                        'recent_leaves' => $group->sortByDesc('date')->take(3)->map(function ($leave) {
                            return [
                                'type' => $leave->type,
                                'category' => $leave->category,
                                'date' => $leave->date->format('Y-m-d'),
                                'status' => $leave->status,
                                'reason' => $leave->reason,
                            ];
                        })->values(),
                    ];
                })->values();
            }

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to generate leave summary: ' . $e->getMessage());
        }
    }
}
