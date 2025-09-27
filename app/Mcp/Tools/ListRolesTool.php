<?php

namespace App\Mcp\Tools;

use App\Models\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class ListRolesTool extends BaseTool
{
    /**
     * The tool's name.
     */
    public function name(): string
    {
        return 'list_roles';
    }

    /**
     * The tool's description.
     */
    public function description(): string
    {
        return 'List all roles in the company with their details including department, level, permissions, and employee count. Can filter by department and active status.';
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

            // Get all roles
            $query = Role::withCount('employees')
                ->active()
                ->orderBy('department')
                ->orderBy('level', 'desc')
                ->orderBy('name');

            // Execute the query
            $roles = $query->get();

            // Format the results
            $results = $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'department' => $role->department,
                    'level' => $role->level,
                    'permissions' => $role->permissions,
                    'is_active' => $role->is_active,
                    'employee_count' => $role->employees_count,
                    'created_at' => $role->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $role->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            // Calculate summary statistics
            $summary = [
                'total_roles' => $roles->count(),
                'active_roles' => $roles->where('is_active', true)->count(),
                'by_department' => $roles->groupBy('department')->map->count(),
                'by_level' => $roles->groupBy('level')->map->count(),
                'total_employees' => $roles->sum('employees_count'),
            ];

            // Get unique departments for reference
            $departments = Role::distinct()->pluck('department')->filter()->sort()->values();

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Retrieved {$results->count()} role(s)",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'team' => $user->team ? $user->team->name : null,
                    'designation' => $user->designation ? $user->designation->name : null,
                ],
                'summary' => $summary,
                'departments' => $departments,
                'roles' => $results->toArray(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve roles: ' . $e->getMessage());
        }
    }
}
