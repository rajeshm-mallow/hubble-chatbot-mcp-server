<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class UserTool extends BaseTool
{
    /**
     * Get the tool name
     */
    public function name(): string
    {
        return 'user_management';
    }

    /**
     * Get the tool description
     */
    public function description(): string
    {
        return 'Comprehensive user management tool for creating, updating, and managing user accounts';
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
     * Handle the tool execution
     */
    public function handle(Request $request): Response
    {
        try {
            // Get current user from email header
            $user = $this->getCurrentUserOrFail();

            // Load relationships
            $user->load(['roles', 'team', 'designation', 'branch', 'reportingPersons', 'subordinates', 'userDetails']);

            // Format the user data
            $userData = [
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
                        'description' => $role->description,
                    ];
                }),
                'reporting_persons' => $user->reportingPersons->map(function ($person) {
                    return [
                        'id' => $person->id,
                        'name' => $person->name,
                        'email' => $person->email,
                        'employee_id' => $person->employee_id,
                    ];
                }),
                'subordinates' => $user->subordinates->map(function ($subordinate) {
                    return [
                        'id' => $subordinate->id,
                        'name' => $subordinate->name,
                        'email' => $subordinate->email,
                        'employee_id' => $subordinate->employee_id,
                    ];
                }),
                'user_details' => $user->userDetails ? $user->userDetails->toArray() : null,
            ];

            return Response::json([
                'success' => true,
                'message' => 'User details retrieved successfully',
                'user' => $userData,
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve user details: ' . $e->getMessage());
        }
    }

}
