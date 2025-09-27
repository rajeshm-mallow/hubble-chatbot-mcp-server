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
            'properties' => [],
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        try {
            // Get current user from email header
            $user = $this->getCurrentUserOrFail();

            // Build the base query for current user's team/department
            $query = Leave::with(['user', 'approvedBy', 'appliedBy']);

            // Filter by user's team/department
            if ($user->team) {
                $query->whereHas('user', function ($q) use ($user) {
                    $q->where('team_id', $user->team_id);
                });
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
                'message' => "Leave summary generated for {$leaves->count()} leave record(s) in {$user->team->name ?? 'your department'}",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'team' => $user->team ? $user->team->name : null,
                    'designation' => $user->designation ? $user->designation->name : null,
                ],
                'statistics' => $stats,
                'top_employees_by_leave' => $topEmployeesByLeave,
                'employees' => $leaves->groupBy('user_id')->map(function ($group) {
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
                })->values(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to generate leave summary: ' . $e->getMessage());
        }
    }
}
