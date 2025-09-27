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
            'properties' => [
                'department' => [
                    'type' => 'string',
                    'description' => 'Filter roles by department',
                ],
                'include_inactive' => [
                    'type' => 'boolean',
                    'description' => 'Whether to include inactive roles (default: false)',
                    'default' => false,
                ],
                'level' => [
                    'type' => 'integer',
                    'description' => 'Filter roles by level (1-10)',
                    'minimum' => 1,
                    'maximum' => 10,
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Search roles by name or description',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results to return (default: 50)',
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
            $department = $request->get('department');
            $includeInactive = $request->get('include_inactive', false);
            $level = $request->get('level');
            $search = $request->get('search');
            $limit = $request->get('limit', 50);

            // Build the query
            $query = Role::withCount('employees');

            // Filter by department
            if ($department) {
                $query->byDepartment($department);
            }

            // Filter by level
            if ($level) {
                $query->where('level', $level);
            }

            // Search by name or description
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter out inactive roles unless specifically requested
            if (!$includeInactive) {
                $query->active();
            }

            // Order by department and level
            $query->orderBy('department')
                  ->orderBy('level', 'desc')
                  ->orderBy('name');

            // Execute the query with limit
            $roles = $query->limit($limit)->get();

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
                'inactive_roles' => $roles->where('is_active', false)->count(),
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
                'summary' => $summary,
                'departments' => $departments,
                'roles' => $results->toArray(),
            ];

            // Add filter information
            $filters = [];
            if ($department) $filters[] = "Department: {$department}";
            if ($level) $filters[] = "Level: {$level}";
            if ($search) $filters[] = "Search: {$search}";
            if (!$includeInactive) $filters[] = "Active only";

            if (!empty($filters)) {
                $responseData['applied_filters'] = $filters;
            }

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve roles: ' . $e->getMessage());
        }
    }
}
