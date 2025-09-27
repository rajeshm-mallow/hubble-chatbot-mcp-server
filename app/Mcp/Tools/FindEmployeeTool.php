<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class FindEmployeeTool extends BaseTool
{
    /**
     * The tool's name.
     */
    public function name(): string
    {
        return 'find_employee';
    }

    /**
     * The tool's description.
     */
    public function description(): string
    {
        return 'Find an employee by name, employee ID, or email address. Returns detailed employee information including role, department, and contact details.';
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
            $user->load(['roles', 'team', 'designation', 'branch']);

            // Format the user data
            $employeeData = [
                'id' => $user->id,
                'employee_id' => $user->employee_id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'status' => $user->status,
                'active_status' => $user->active_status,
                'is_employed' => $user->is_employed,
                'team' => $user->team ? [
                    'id' => $user->team->id,
                    'name' => $user->team->name,
                    'type' => $user->team->type,
                ] : null,
                'designation' => $user->designation ? [
                    'id' => $user->designation->id,
                    'name' => $user->designation->name,
                    'type' => $user->designation->type,
                ] : null,
                'branch' => $user->branch ? [
                    'id' => $user->branch->id,
                    'name' => $user->branch->name,
                    'city' => $user->branch->city,
                    'state' => $user->branch->state,
                ] : null,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                    ];
                }),
            ];

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Employee details retrieved for {$user->name}",
                'employee' => $employeeData,
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve employee details: ' . $e->getMessage());
        }
    }
}
