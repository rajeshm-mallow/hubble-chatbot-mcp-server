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

            // Build the query for current user's leaves
            $query = Leave::with(['user', 'approvedBy', 'appliedBy'])
                ->byUser($user->id);

            // Order by date (most recent first)
            $query->orderBy('date', 'desc');

            // Execute the query
            $leaves = $query->get();

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
                'message' => "Retrieved {$results->count()} leave record(s) for {$user->name}",
                'employee' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'team' => $user->team ? $user->team->name : null,
                    'designation' => $user->designation ? $user->designation->name : null,
                ],
                'summary' => $summary,
                'leaves' => $results->toArray(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve employee leave records: ' . $e->getMessage());
        }
    }
}
