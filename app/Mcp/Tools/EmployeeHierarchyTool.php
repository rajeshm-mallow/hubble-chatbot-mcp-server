<?php

namespace App\Mcp\Tools;

use App\Models\Employee;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class EmployeeHierarchyTool extends BaseTool
{
    /**
     * The tool's name.
     */
    public function name(): string
    {
        return 'employee_hierarchy';
    }

    /**
     * The tool's description.
     */
    public function description(): string
    {
        return 'Get the organizational hierarchy starting from a specific employee or manager. Shows reporting structure, subordinates, and team composition.';
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

            // Load relationships
            $user->load(['roles', 'team', 'designation', 'branch', 'reportingPersons', 'subordinates']);

            $hierarchy = [];

            // Get reporting persons (managers)
            $hierarchy['managers'] = $user->reportingPersons->map(function ($manager) {
                return [
                    'level' => 1,
                    'id' => $manager->id,
                    'name' => $manager->name,
                    'email' => $manager->email,
                    'employee_id' => $manager->employee_id,
                    'team' => $manager->team ? $manager->team->name : null,
                    'designation' => $manager->designation ? $manager->designation->name : null,
                    'active_status' => $manager->active_status,
                ];
            });

            // Get subordinates
            $hierarchy['subordinates'] = $user->subordinates->map(function ($subordinate) {
                return [
                    'level' => 1,
                    'id' => $subordinate->id,
                    'name' => $subordinate->name,
                    'email' => $subordinate->email,
                    'employee_id' => $subordinate->employee_id,
                    'team' => $subordinate->team ? $subordinate->team->name : null,
                    'designation' => $subordinate->designation ? $subordinate->designation->name : null,
                    'active_status' => $subordinate->active_status,
                ];
            });

            // Get team statistics
            $teamStats = [
                'total_team_size' => $user->subordinates->count() + 1,
                'direct_reports' => $user->subordinates->count(),
                'managers_count' => $user->reportingPersons->count(),
                'by_designation' => $user->subordinates->groupBy('designation.name')->map->count(),
                'by_status' => $user->subordinates->groupBy('active_status')->map->count(),
            ];

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Hierarchy generated for {$user->name}",
                'starting_employee' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'team' => $user->team ? $user->team->name : null,
                    'designation' => $user->designation ? $user->designation->name : null,
                    'active_status' => $user->active_status,
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                        ];
                    }),
                ],
                'hierarchy' => $hierarchy,
                'team_statistics' => $teamStats
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to generate employee hierarchy: ' . $e->getMessage());
        }
    }

}
