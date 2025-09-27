<?php

namespace App\Mcp\Tools;

use App\Models\Employee;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class DepartmentEmployeesTool extends BaseTool
{
    /**
     * The tool's name.
     */
    public function name(): string
    {
        return 'department_employees';
    }

    /**
     * The tool's description.
     */
    public function description(): string
    {
        return 'Get all employees in a specific department with their roles and contact information. Useful for team management and department overview.';
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

            // Get employees from user's team/department
            $query = User::with(['roles', 'team', 'designation', 'branch'])
                ->where('team_id', $user->team_id);

            // Filter out inactive employees
            $query->active();

            // Order by name
            $query->orderBy('name');

            // Execute the query
            $employees = $query->get();

            // Format the results
            $results = $employees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'username' => $employee->username,
                    'status' => $employee->status,
                    'active_status' => $employee->active_status,
                    'is_employed' => $employee->is_employed,
                    'team' => $employee->team ? [
                        'id' => $employee->team->id,
                        'name' => $employee->team->name,
                        'type' => $employee->team->type,
                    ] : null,
                    'designation' => $employee->designation ? [
                        'id' => $employee->designation->id,
                        'name' => $employee->designation->name,
                        'type' => $employee->designation->type,
                    ] : null,
                    'branch' => $employee->branch ? [
                        'id' => $employee->branch->id,
                        'name' => $employee->branch->name,
                        'city' => $employee->branch->city,
                        'state' => $employee->branch->state,
                    ] : null,
                    'roles' => $employee->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                        ];
                    }),
                ];
            });

            // Calculate department statistics
            $stats = [
                'total_employees' => $employees->count(),
                'active_employees' => $employees->where('active_status', 'active')->count(),
                'inactive_employees' => $employees->where('active_status', 'inactive')->count(),
                'by_designation' => $employees->groupBy('designation.name')->map->count(),
                'by_status' => $employees->groupBy('status')->map->count(),
                'by_branch' => $employees->groupBy('branch.name')->map->count(),
            ];

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => sprintf(
                    "Retrieved %d employee(s) from %s",
                    $results->count(),
                    $user->team->name ?? 'your department'
                ),                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'team' => $user->team ? $user->team->name : null,
                    'designation' => $user->designation ? $user->designation->name : null,
                ],
                'department' => $user->team ? $user->team->name : 'Unknown',
                'statistics' => $stats,
                'employees' => $results->toArray(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve department employees: ' . $e->getMessage());
        }
    }
}
