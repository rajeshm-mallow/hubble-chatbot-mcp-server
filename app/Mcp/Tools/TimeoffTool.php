<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Models\Timeoff;
use App\Models\Team;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class TimeoffTool extends Tool
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
            'properties' => [
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'Get timeoff for specific user',
                ],
                'user_name' => [
                    'type' => 'string',
                    'description' => 'Get timeoff for user by name',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Get timeoff for specific team',
                ],
                'team_name' => [
                    'type' => 'string',
                    'description' => 'Get timeoff for team by name',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Filter by timeoff status',
                    'enum' => ['pending', 'approved', 'rejected'],
                ],
                'start_date' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Filter from this date (YYYY-MM-DD)',
                ],
                'end_date' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Filter until this date (YYYY-MM-DD)',
                ],
                'year' => [
                    'type' => 'integer',
                    'description' => 'Filter by specific year',
                ],
                'month' => [
                    'type' => 'integer',
                    'description' => 'Filter by specific month (1-12)',
                ],
                'today' => [
                    'type' => 'boolean',
                    'description' => 'Get timeoffs for today only',
                    'default' => false,
                ],
                'upcoming' => [
                    'type' => 'boolean',
                    'description' => 'Get upcoming timeoffs only',
                    'default' => false,
                ],
                'include_team_details' => [
                    'type' => 'boolean',
                    'description' => 'Include team and designation information',
                    'default' => false,
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results (default: 50)',
                    'default' => 50,
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
            $userId = $request->get('user_id');
            $userName = $request->get('user_name');
            $teamId = $request->get('team_id');
            $teamName = $request->get('team_name');
            $status = $request->get('status');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $year = $request->get('year');
            $month = $request->get('month');
            $today = $request->get('today', false);
            $upcoming = $request->get('upcoming', false);
            $includeTeamDetails = $request->get('include_team_details', false);
            $limit = $request->get('limit', 50);

            // Build the query
            $query = Timeoff::with(['user', 'appliedBy', 'approvedBy']);

            // Apply user filter
            if ($userName && !$userId) {
                $user = User::where('name', 'like', "%{$userName}%")->first();
                if (!$user) {
                    return Response::error("User with name '{$userName}' not found");
                }
                $userId = $user->id;
            }

            if ($userId) {
                $query->byUser($userId);
            }

            // Apply team filter
            if ($teamName && !$teamId) {
                $team = Team::where('name', 'like', "%{$teamName}%")->first();
                if (!$team) {
                    return Response::error("Team with name '{$teamName}' not found");
                }
                $teamId = $team->id;
            }

            if ($teamId) {
                $query->whereHas('user', function ($q) use ($teamId) {
                    $q->where('team_id', $teamId);
                });
            }

            // Apply status filter
            if ($status) {
                $query->byStatus($status);
            }

            // Apply date filters
            if ($today) {
                $query->whereDate('date', today());
            } elseif ($upcoming) {
                $query->whereDate('date', '>=', today());
            } elseif ($startDate && $endDate) {
                $query->byDateRange($startDate, $endDate);
            } elseif ($year && $month) {
                $query->whereYear('date', $year)->whereMonth('date', $month);
            } elseif ($year) {
                $query->whereYear('date', $year);
            }

            // Order by date (most recent first)
            $query->orderBy('date', 'desc');

            // Execute the query with limit
            $timeoffs = $query->limit($limit)->get();

            // Format the results
            $results = $timeoffs->map(function ($timeoff) use ($includeTeamDetails) {
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

                if ($includeTeamDetails && $timeoff->user) {
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
                'by_user' => $timeoffs->filter(function ($timeoff) {
                    return $timeoff->user !== null;
                })->groupBy(function ($timeoff) {
                    return $timeoff->user->name;
                })->map->count(),
                'today_timeoffs' => $timeoffs->where('date', today())->count(),
                'upcoming_timeoffs' => $timeoffs->where('date', '>=', today())->count(),
            ];

            if ($includeTeamDetails) {
                $summary['by_team'] = $timeoffs->filter(function ($timeoff) {
                    return $timeoff->user && $timeoff->user->team;
                })->groupBy(function ($timeoff) {
                    return $timeoff->user->team->name;
                })->map->count();
                $summary['by_designation'] = $timeoffs->filter(function ($timeoff) {
                    return $timeoff->user && $timeoff->user->designation;
                })->groupBy(function ($timeoff) {
                    return $timeoff->user->designation->name;
                })->map->count();
            }

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Retrieved {$results->count()} timeoff record(s)",
                'filters_applied' => array_filter([
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'team_id' => $teamId,
                    'team_name' => $teamName,
                    'status' => $status,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'year' => $year,
                    'month' => $month,
                    'today' => $today,
                    'upcoming' => $upcoming,
                ]),
                'summary' => $summary,
                'timeoffs' => $results->toArray(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve timeoff data: ' . $e->getMessage());
        }
    }
}
