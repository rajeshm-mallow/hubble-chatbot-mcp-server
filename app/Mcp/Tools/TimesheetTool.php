<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Models\TimesheetEntry;
use App\Models\Project;
use App\Models\Team;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class TimesheetTool extends BaseTool
{
    /**
     * The tool's name.
     */
    public function name(): string
    {
        return 'timesheet_data';
    }

    /**
     * The tool's description.
     */
    public function description(): string
    {
        return 'Get timesheet entries for users, teams, or projects. Track work logs, task efforts, and project time allocation with detailed analytics.';
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
                    'description' => 'Get timesheet for specific user',
                ],
                'user_name' => [
                    'type' => 'string',
                    'description' => 'Get timesheet for user by name',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Get timesheet for specific team',
                ],
                'team_name' => [
                    'type' => 'string',
                    'description' => 'Get timesheet for team by name',
                ],
                'project_id' => [
                    'type' => 'integer',
                    'description' => 'Get timesheet for specific project',
                ],
                'project_name' => [
                    'type' => 'string',
                    'description' => 'Get timesheet for project by name',
                ],
                'start_date' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Start date for filtering (YYYY-MM-DD)',
                ],
                'end_date' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'End date for filtering (YYYY-MM-DD)',
                ],
                'year' => [
                    'type' => 'integer',
                    'description' => 'Filter by specific year',
                ],
                'month' => [
                    'type' => 'integer',
                    'description' => 'Filter by specific month (1-12)',
                ],
                'week' => [
                    'type' => 'integer',
                    'description' => 'Filter by specific week (1-52)',
                ],
                'include_details' => [
                    'type' => 'boolean',
                    'description' => 'Include detailed task and module information',
                    'default' => false,
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results (default: 50)',
                    'default' => 50,
                    'minimum' => 1,
                    'maximum' => 200,
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
            $projectId = $request->get('project_id');
            $projectName = $request->get('project_name');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $year = $request->get('year');
            $month = $request->get('month');
            $week = $request->get('week');
            $includeDetails = $request->get('include_details', false);
            $limit = $request->get('limit', 50);

            // Build the query
            $query = TimesheetEntry::with(['user', 'project', 'module', 'task', 'team']);

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
                $query->byTeam($teamId);
            }

            // Apply project filter
            if ($projectName && !$projectId) {
                $project = Project::where('name', 'like', "%{$projectName}%")->first();
                if (!$project) {
                    return Response::error("Project with name '{$projectName}' not found");
                }
                $projectId = $project->id;
            }

            if ($projectId) {
                $query->byProject($projectId);
            }

            // Apply date filters
            if ($startDate && $endDate) {
                $query->byDateRange($startDate, $endDate);
            } elseif ($year && $month) {
                $query->byMonth($year, $month);
            } elseif ($year) {
                $query->whereYear('entry_date', $year);
            } elseif ($week) {
                $query->whereRaw('WEEK(entry_date) = ?', [$week]);
            }

            // Order by entry date (most recent first)
            $query->orderBy('entry_date', 'desc');

            // Execute the query with limit
            $timesheetEntries = $query->limit($limit)->get();

            // Format the results
            $results = $timesheetEntries->map(function ($entry) use ($includeDetails) {
                $data = [
                    'id' => $entry->id,
                    'entry_date' => $entry->entry_date->format('Y-m-d'),
                    'description' => $entry->description,
                    'working_hours' => $entry->working_hours,
                    'approved_hours' => $entry->approved_hours,
                    'authorized_hours' => $entry->authorized_hours,
                    'billed_hours' => $entry->billed_hours,
                    'admin_comments' => $entry->admin_comments,
                    'user' => $entry->user ? [
                        'id' => $entry->user->id,
                        'name' => $entry->user->name,
                        'employee_id' => $entry->user->employee_id,
                    ] : null,
                    'project' => $entry->project ? [
                        'id' => $entry->project->id,
                        'name' => $entry->project->name,
                        'status' => $entry->project->status,
                    ] : null,
                    'team' => $entry->team ? [
                        'id' => $entry->team->id,
                        'name' => $entry->team->name,
                    ] : null,
                ];

                if ($includeDetails) {
                    $data['module'] = $entry->module ? [
                        'id' => $entry->module->id,
                        'name' => $entry->module->name,
                    ] : null;
                    $data['task'] = $entry->task ? [
                        'id' => $entry->task->id,
                        'name' => $entry->task->name,
                        'description' => $entry->task->description,
                    ] : null;
                }

                return $data;
            });

            // Calculate summary statistics
            $summary = [
                'total_entries' => $timesheetEntries->count(),
                'total_working_hours' => $timesheetEntries->sum('working_hours'),
                'total_approved_hours' => $timesheetEntries->sum('approved_hours'),
                'total_authorized_hours' => $timesheetEntries->sum('authorized_hours'),
                'total_billed_hours' => $timesheetEntries->sum('billed_hours'),
                'by_user' => $timesheetEntries->filter(function ($entry) {
                    return $entry->user !== null;
                })->groupBy(function ($entry) {
                    return $entry->user->name;
                })->map(function ($group) {
                    return [
                        'entries' => $group->count(),
                        'total_hours' => $group->sum('working_hours'),
                    ];
                }),
                'by_project' => $timesheetEntries->filter(function ($entry) {
                    return $entry->project !== null;
                })->groupBy(function ($entry) {
                    return $entry->project->name;
                })->map(function ($group) {
                    return [
                        'entries' => $group->count(),
                        'total_hours' => $group->sum('working_hours'),
                    ];
                }),
                'by_team' => $timesheetEntries->filter(function ($entry) {
                    return $entry->team !== null;
                })->groupBy(function ($entry) {
                    return $entry->team->name;
                })->map(function ($group) {
                    return [
                        'entries' => $group->count(),
                        'total_hours' => $group->sum('working_hours'),
                    ];
                }),
            ];

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Retrieved {$results->count()} timesheet entries",
                'filters_applied' => array_filter([
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'team_id' => $teamId,
                    'team_name' => $teamName,
                    'project_id' => $projectId,
                    'project_name' => $projectName,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'year' => $year,
                    'month' => $month,
                    'week' => $week,
                ]),
                'summary' => $summary,
                'timesheet_entries' => $results->toArray(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve timesheet data: ' . $e->getMessage());
        }
    }
}
