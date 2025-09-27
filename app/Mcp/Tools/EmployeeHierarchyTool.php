<?php

namespace App\Mcp\Tools;

use App\Models\Employee;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class EmployeeHierarchyTool extends Tool
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
            'properties' => [
                'employee_id' => [
                    'type' => 'integer',
                    'description' => 'The ID of the employee to start the hierarchy from',
                ],
                'employee_name' => [
                    'type' => 'string',
                    'description' => 'The name of the employee to start the hierarchy from (alternative to employee_id)',
                ],
                'max_depth' => [
                    'type' => 'integer',
                    'description' => 'Maximum depth of hierarchy to show (default: 3)',
                    'default' => 3,
                    'minimum' => 1,
                    'maximum' => 5,
                ],
                'include_inactive' => [
                    'type' => 'boolean',
                    'description' => 'Whether to include inactive employees (default: false)',
                    'default' => false,
                ],
                'direction' => [
                    'type' => 'string',
                    'description' => 'Direction to traverse hierarchy (up, down, both)',
                    'enum' => ['up', 'down', 'both'],
                    'default' => 'both',
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
            $employeeId = $request->get('employee_id');
            $employeeName = $request->get('employee_name');
            $maxDepth = $request->get('max_depth', 3);
            $includeInactive = $request->get('include_inactive', false);
            $direction = $request->get('direction', 'both');

            // Find employee if name is provided instead of ID
            if ($employeeName && !$employeeId) {
                $employee = Employee::where('name', 'like', "%{$employeeName}%")->first();
                if (!$employee) {
                    return Response::error("Employee with name '{$employeeName}' not found");
                }
                $employeeId = $employee->id;
            }

            // Validate employee ID
            if (!$employeeId) {
                return Response::error('Either employee_id or employee_name must be provided');
            }

            // Get the starting employee
            $startEmployee = Employee::with(['role', 'manager'])->find($employeeId);
            if (!$startEmployee) {
                return Response::error("Employee with ID {$employeeId} not found");
            }

            $hierarchy = [];

            // Build hierarchy based on direction
            if ($direction === 'up' || $direction === 'both') {
                $hierarchy['managers'] = $this->getManagers($startEmployee, $maxDepth, $includeInactive);
            }

            if ($direction === 'down' || $direction === 'both') {
                $hierarchy['subordinates'] = $this->getSubordinates($startEmployee, $maxDepth, $includeInactive);
            }

            // Get team statistics
            $teamStats = $this->getTeamStatistics($startEmployee, $includeInactive);

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Hierarchy generated for {$startEmployee->name}",
                'starting_employee' => [
                    'id' => $startEmployee->id,
                    'name' => $startEmployee->name,
                    'email' => $startEmployee->email,
                    'department' => $startEmployee->department,
                    'position' => $startEmployee->position,
                    'role' => $startEmployee->role ? [
                        'name' => $startEmployee->role->name,
                        'level' => $startEmployee->role->level,
                    ] : null,
                ],
                'hierarchy' => $hierarchy,
                'team_statistics' => $teamStats,
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to generate employee hierarchy: ' . $e->getMessage());
        }
    }

    /**
     * Get managers up the hierarchy.
     */
    private function getManagers(Employee $employee, int $maxDepth, bool $includeInactive): array
    {
        $managers = [];
        $currentEmployee = $employee;
        $depth = 0;

        while ($currentEmployee->manager && $depth < $maxDepth) {
            $currentEmployee = $currentEmployee->manager;
            $depth++;

            if (!$includeInactive && $currentEmployee->status !== 'active') {
                continue;
            }

            $managers[] = [
                'level' => $depth,
                'id' => $currentEmployee->id,
                'name' => $currentEmployee->name,
                'email' => $currentEmployee->email,
                'department' => $currentEmployee->department,
                'position' => $currentEmployee->position,
                'status' => $currentEmployee->status,
                'role' => $currentEmployee->role ? [
                    'name' => $currentEmployee->role->name,
                    'level' => $currentEmployee->role->level,
                ] : null,
            ];
        }

        return $managers;
    }

    /**
     * Get subordinates down the hierarchy.
     */
    private function getSubordinates(Employee $employee, int $maxDepth, bool $includeInactive): array
    {
        return $this->getSubordinatesRecursive($employee, 0, $maxDepth, $includeInactive);
    }

    /**
     * Recursively get subordinates.
     */
    private function getSubordinatesRecursive(Employee $employee, int $currentDepth, int $maxDepth, bool $includeInactive): array
    {
        if ($currentDepth >= $maxDepth) {
            return [];
        }

        $subordinates = [];
        $directSubordinates = $employee->subordinates()->with(['role'])->get();

        foreach ($directSubordinates as $subordinate) {
            if (!$includeInactive && $subordinate->status !== 'active') {
                continue;
            }

            $subordinateData = [
                'level' => $currentDepth + 1,
                'id' => $subordinate->id,
                'name' => $subordinate->name,
                'email' => $subordinate->email,
                'department' => $subordinate->department,
                'position' => $subordinate->position,
                'status' => $subordinate->status,
                'role' => $subordinate->role ? [
                    'name' => $subordinate->role->name,
                    'level' => $subordinate->role->level,
                ] : null,
                'subordinates' => $this->getSubordinatesRecursive($subordinate, $currentDepth + 1, $maxDepth, $includeInactive),
            ];

            $subordinates[] = $subordinateData;
        }

        return $subordinates;
    }

    /**
     * Get team statistics.
     */
    private function getTeamStatistics(Employee $employee, bool $includeInactive): array
    {
        $query = Employee::where('id', $employee->id)
            ->orWhere('manager_id', $employee->id);

        if (!$includeInactive) {
            $query->where('status', 'active');
        }

        $teamMembers = $query->get();

        return [
            'total_team_size' => $teamMembers->count(),
            'direct_reports' => $teamMembers->where('manager_id', $employee->id)->count(),
            'by_department' => $teamMembers->groupBy('department')->map->count(),
            'by_status' => $teamMembers->groupBy('status')->map->count(),
            'by_role' => $teamMembers->groupBy('role.name')->map->count(),
        ];
    }
}
