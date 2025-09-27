<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Models\Timeoff;
use App\Models\Team;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class TimeoffTool extends BaseTool
{
    /**
     * The tool's name.
     */
    public function name(): string
    {
        return 'timeoff_data';
    }

    /**
     * The tool's description.
     */
    public function description(): string
    {
        return 'Get time-off records for employees including short-term and ad-hoc time-off requests with approval status and team insights.';
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

            // Build the query for current user's timeoffs
            $query = Timeoff::with(['user', 'appliedBy', 'approvedBy'])
                ->byUser($user->id);

            // Order by date (most recent first)
            $query->orderBy('date', 'desc');

            // Execute the query
            $timeoffs = $query->get();

            // Format the results
            $results = $timeoffs->map(function ($timeoff) {
                $data = [
                    'id' => $timeoff->id,
                    'date' => $timeoff->date->format('Y-m-d'),
                    'start_time' => $timeoff->start_time->format('H:i:s'),
                    'end_time' => $timeoff->end_time->format('H:i:s'),
                    'reason' => $timeoff->reason,
                    'status' => $timeoff->status,
                    'reject_reason' => $timeoff->reject_reason,
                    'history' => $timeoff->history,
                    'time_changes' => $timeoff->time_changes,
                    'user' => $timeoff->user ? [
                        'id' => $timeoff->user->id,
                        'name' => $timeoff->user->name,
                        'employee_id' => $timeoff->user->employee_id,
                        'email' => $timeoff->user->email,
                    ] : null,
                    'applied_by' => $timeoff->appliedBy ? [
                        'id' => $timeoff->appliedBy->id,
                        'name' => $timeoff->appliedBy->name,
                        'email' => $timeoff->appliedBy->email,
                    ] : null,
                    'approved_by' => $timeoff->approvedBy ? [
                        'id' => $timeoff->approvedBy->id,
                        'name' => $timeoff->approvedBy->name,
                        'email' => $timeoff->approvedBy->email,
                    ] : null,
                    'created_at' => $timeoff->created_at->format('Y-m-d H:i:s'),
                ];

                // Include team details for user's timeoffs
                if ($timeoff->user) {
                    $data['user']['team'] = $timeoff->user->team ? [
                        'id' => $timeoff->user->team->id,
                        'name' => $timeoff->user->team->name,
                        'type' => $timeoff->user->team->type,
                    ] : null;
                    $data['user']['designation'] = $timeoff->user->designation ? [
                        'id' => $timeoff->user->designation->id,
                        'name' => $timeoff->user->designation->name,
                        'type' => $timeoff->user->designation->type,
                    ] : null;
                }

                return $data;
            });

            // Calculate summary statistics
            $summary = [
                'total_timeoffs' => $timeoffs->count(),
                'by_status' => $timeoffs->groupBy('status')->map->count(),
                'today_timeoffs' => $timeoffs->where('date', today())->count(),
                'upcoming_timeoffs' => $timeoffs->where('date', '>=', today())->count(),
                'by_team' => $timeoffs->filter(function ($timeoff) {
                    return $timeoff->user && $timeoff->user->team;
                })->groupBy(function ($timeoff) {
                    return $timeoff->user->team->name;
                })->map->count(),
                'by_designation' => $timeoffs->filter(function ($timeoff) {
                    return $timeoff->user && $timeoff->user->designation;
                })->groupBy(function ($timeoff) {
                    return $timeoff->user->designation->name;
                })->map->count(),
            ];

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Retrieved {$results->count()} timeoff record(s) for {$user->name}",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'team' => $user->team ? $user->team->name : null,
                    'designation' => $user->designation ? $user->designation->name : null,
                ],
                'summary' => $summary,
                'timeoffs' => $results->toArray(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve timeoff data: ' . $e->getMessage());
        }
    }
}
