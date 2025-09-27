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
            'properties' => [
                'department' => [
                    'type' => 'string',
                    'description' => 'The department name to get employees for',
                ],
                'include_inactive' => [
                    'type' => 'boolean',
                    'description' => 'Whether to include inactive employees (default: false)',
                    'default' => false,
                ],
                'sort_by' => [
                    'type' => 'string',
                    'description' => 'Sort employees by field (name, position, hire_date)',
                    'enum' => ['name', 'position', 'hire_date', 'salary'],
                    'default' => 'name',
                ],
                'sort_order' => [
                    'type' => 'string',
                    'description' => 'Sort order (asc or desc)',
                    'enum' => ['asc', 'desc'],
                    'default' => 'asc',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results to return (default: 50)',
                    'default' => 50,
                    'minimum' => 1,
                    'maximum' => 200,
                ],
            ],
            'required' => ['department'],
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
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $limit = $request->get('limit', 50);

            // Validate department
            if (empty(trim($department))) {
                return Response::error('Department name cannot be empty');
            }

            // Build the query
            $query = Employee::with(['role', 'manager'])
                ->where('department', $department);

            // Filter out inactive employees unless specifically requested
            if (!$includeInactive) {
                $query->active();
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortOrder);

            // Execute the query with limit
            $employees = $query->limit($limit)->get();

            // Format the results
            $results = $employees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'employee_id' => $employee->employee_id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'position' => $employee->position,
                    'hire_date' => $employee->hire_date?->format('Y-m-d'),
                    'salary' => $employee->salary,
                    'status' => $employee->status,
                    'role' => $employee->role ? [
                        'id' => $employee->role->id,
                        'name' => $employee->role->name,
                        'level' => $employee->role->level,
                    ] : null,
                    'manager' => $employee->manager ? [
                        'id' => $employee->manager->id,
                        'name' => $employee->manager->name,
                        'email' => $employee->manager->email,
                    ] : null,
                ];
            });

            // Calculate department statistics
            $stats = [
                'total_employees' => $employees->count(),
                'active_employees' => $employees->where('status', 'active')->count(),
                'inactive_employees' => $employees->where('status', 'inactive')->count(),
                'by_position' => $employees->groupBy('position')->map->count(),
                'by_role' => $employees->groupBy('role.name')->map->count(),
                'average_salary' => $employees->where('salary', '>', 0)->avg('salary'),
                'newest_hire' => $employees->max('hire_date')?->format('Y-m-d'),
                'oldest_hire' => $employees->min('hire_date')?->format('Y-m-d'),
            ];

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Retrieved {$results->count()} employee(s) from {$department} department",
                'department' => $department,
                'statistics' => $stats,
                'employees' => $results->toArray(),
            ];

            // Add suggestions if no results found
            if ($results->isEmpty()) {
                $responseData['suggestions'] = [
                    'Check if the department name is spelled correctly',
                    'Try including inactive employees (use include_inactive: true)',
                    'Verify that employees exist in this department',
                ];
            }

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve department employees: ' . $e->getMessage());
        }
    }
}
