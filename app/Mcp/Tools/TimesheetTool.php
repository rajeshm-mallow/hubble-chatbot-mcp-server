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

            // Build the query for current user's timesheet entries
            $query = TimesheetEntry::with(['user', 'project', 'module', 'task', 'team'])
                ->byUser($user->id);

            // Order by entry date (most recent first)
            $query->orderBy('entry_date', 'desc');

            // Execute the query
            $timesheetEntries = $query->get();

            // Format the results
            $results = $timesheetEntries->map(function ($entry) {
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
                    'module' => $entry->module ? [
                        'id' => $entry->module->id,
                        'name' => $entry->module->name,
                    ] : null,
                    'task' => $entry->task ? [
                        'id' => $entry->task->id,
                        'name' => $entry->task->name,
                        'description' => $entry->task->description,
                    ] : null,
                ];

                return $data;
            });

            // Calculate summary statistics
            $summary = [
                'total_entries' => $timesheetEntries->count(),
                'total_working_hours' => $timesheetEntries->sum('working_hours'),
                'total_approved_hours' => $timesheetEntries->sum('approved_hours'),
                'total_authorized_hours' => $timesheetEntries->sum('authorized_hours'),
                'total_billed_hours' => $timesheetEntries->sum('billed_hours'),
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
                'message' => "Retrieved {$results->count()} timesheet entries for {$user->name}",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'team' => $user->team ? $user->team->name : null,
                    'designation' => $user->designation ? $user->designation->name : null,
                ],
                'summary' => $summary,
                'timesheet_entries' => $results->toArray(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve timesheet data: ' . $e->getMessage());
        }
    }
}
