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
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'description' => 'Action to perform: create, update, delete, activate, deactivate, get_details, or search',
                    'enum' => ['create', 'update', 'delete', 'activate', 'deactivate', 'get_details', 'search'],
                ],
                'user_id' => [
                    'type' => 'integer',
                    'description' => 'User ID (required for update, delete, activate, deactivate, get_details)',
                ],
                'employee_id' => [
                    'type' => 'string',
                    'description' => 'Employee ID (required for create, optional for update)',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Full name of the user',
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'Email address',
                ],
                'username' => [
                    'type' => 'string',
                    'description' => 'Username',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'User status: intern, employee, contractor, etc.',
                ],
                'active_status' => [
                    'type' => 'string',
                    'description' => 'Active status: active, inactive',
                    'enum' => ['active', 'inactive'],
                ],
                'is_employed' => [
                    'type' => 'boolean',
                    'description' => 'Employment status',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Team ID',
                ],
                'designation_id' => [
                    'type' => 'integer',
                    'description' => 'Designation ID',
                ],
                'branch_id' => [
                    'type' => 'integer',
                    'description' => 'Branch ID',
                ],
                'role_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Array of role IDs to assign',
                ],
                'reporting_person_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Array of reporting person IDs',
                ],
                'subordinate_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Array of subordinate IDs',
                ],
                'search_term' => [
                    'type' => 'string',
                    'description' => 'Search term for finding users',
                ],
                'include_inactive' => [
                    'type' => 'boolean',
                    'description' => 'Include inactive users in search',
                    'default' => false,
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results to return',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 100,
                ],
            ],
            'required' => ['action'],
        ];
    }

    /**
     * Handle the tool execution
     */
    public function handle(Request $request): Response
    {
        try {
            $action = $request->get('action');

            switch ($action) {
                case 'create':
                    return $this->createUser($request);
                case 'update':
                    return $this->updateUser($request);
                case 'delete':
                    return $this->deleteUser($request);
                case 'activate':
                    return $this->activateUser($request);
                case 'deactivate':
                    return $this->deactivateUser($request);
                case 'get_details':
                    return $this->getUserDetails($request);
                case 'search':
                    return $this->searchUsers($request);
                default:
                    return Response::error('Invalid action. Supported actions: create, update, delete, activate, deactivate, get_details, search');
            }

        } catch (\Exception $e) {
            return Response::error('Failed to process user management request: ' . $e->getMessage());
        }
    }

    /**
     * Create a new user
     */
    private function createUser(Request $request): Response
    {
        try {
            $userData = [
                'employee_id' => $request->get('employee_id'),
                'name' => $request->get('name'),
                'email' => $request->get('email'),
                'username' => $request->get('username'),
                'status' => $request->get('status'),
                'active_status' => $request->get('active_status', 'active'),
                'is_employed' => $request->get('is_employed', true),
                'team_id' => $request->get('team_id'),
                'designation_id' => $request->get('designation_id'),
                'branch_id' => $request->get('branch_id'),
            ];

            // Remove null values
            $userData = array_filter($userData, function($value) {
                return $value !== null;
            });

            $user = User::create($userData);

            // Assign roles if provided
            if (!empty($request->get('role_ids'))) {
                $user->roles()->attach($request->get('role_ids'));
            }

            // Assign reporting persons if provided
            if (!empty($request->get('reporting_person_ids'))) {
                $user->reportingPersons()->attach($request->get('reporting_person_ids'));
            }

            // Assign subordinates if provided
            if (!empty($request->get('subordinate_ids'))) {
                $user->subordinates()->attach($request->get('subordinate_ids'));
            }

            // Load relationships
            $user->load(['roles', 'team', 'designation', 'branch', 'reportingPersons', 'subordinates']);

            return Response::json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => $user->toArray(),
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing user
     */
    private function updateUser(Request $request): Response
    {
        try {
            $userId = $request->get('user_id');
            if (!$userId) {
                return Response::error('User ID is required for update');
            }

            $user = User::find($userId);
            if (!$user) {
                return Response::error('User not found');
            }

            $updateData = [
                'employee_id' => $request->get('employee_id'),
                'name' => $request->get('name'),
                'email' => $request->get('email'),
                'username' => $request->get('username'),
                'status' => $request->get('status'),
                'active_status' => $request->get('active_status'),
                'is_employed' => $request->get('is_employed'),
                'team_id' => $request->get('team_id'),
                'designation_id' => $request->get('designation_id'),
                'branch_id' => $request->get('branch_id'),
            ];

            // Remove null values
            $updateData = array_filter($updateData, function($value) {
                return $value !== null;
            });

            $user->update($updateData);

            // Update roles if provided
            if ($request->has('role_ids')) {
                $user->roles()->sync($request->get('role_ids'));
            }

            // Update reporting persons if provided
            if ($request->has('reporting_person_ids')) {
                $user->reportingPersons()->sync($request->get('reporting_person_ids'));
            }

            // Update subordinates if provided
            if ($request->has('subordinate_ids')) {
                $user->subordinates()->sync($request->get('subordinate_ids'));
            }

            // Load relationships
            $user->load(['roles', 'team', 'designation', 'branch', 'reportingPersons', 'subordinates']);

            return Response::json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => $user->toArray(),
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Delete a user
     */
    private function deleteUser(Request $request): Response
    {
        try {
            $userId = $request->get('user_id');
            if (!$userId) {
                return Response::error('User ID is required for deletion');
            }

            $user = User::find($userId);
            if (!$user) {
                return Response::error('User not found');
            }

            $user->delete();

            return Response::json([
                'success' => true,
                'message' => 'User deleted successfully',
                'deleted_user_id' => $userId,
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Activate a user
     */
    private function activateUser(Request $request): Response
    {
        try {
            $userId = $request->get('user_id');
            if (!$userId) {
                return Response::error('User ID is required for activation');
            }

            $user = User::find($userId);
            if (!$user) {
                return Response::error('User not found');
            }

            $user->update(['active_status' => 'active']);

            return Response::json([
                'success' => true,
                'message' => 'User activated successfully',
                'user' => $user->fresh(['roles', 'team', 'designation', 'branch'])->toArray(),
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to activate user: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate a user
     */
    private function deactivateUser(Request $request): Response
    {
        try {
            $userId = $request->get('user_id');
            if (!$userId) {
                return Response::error('User ID is required for deactivation');
            }

            $user = User::find($userId);
            if (!$user) {
                return Response::error('User not found');
            }

            $user->update(['active_status' => 'inactive']);

            return Response::json([
                'success' => true,
                'message' => 'User deactivated successfully',
                'user' => $user->fresh(['roles', 'team', 'designation', 'branch'])->toArray(),
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to deactivate user: ' . $e->getMessage());
        }
    }

    /**
     * Get user details
     */
    private function getUserDetails(Request $request): Response
    {
        try {
            $userId = $request->get('user_id');
            if (!$userId) {
                return Response::error('User ID is required');
            }

            $user = User::with(['roles', 'team', 'designation', 'branch', 'reportingPersons', 'subordinates', 'userDetails'])
                ->find($userId);

            if (!$user) {
                return Response::error('User not found');
            }

            return Response::json([
                'success' => true,
                'message' => 'User details retrieved successfully',
                'user' => $user->toArray(),
            ]);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve user details: ' . $e->getMessage());
        }
    }

    /**
     * Search users
     */
    private function searchUsers(Request $request): Response
    {
        try {
            $searchTerm = $request->get('search_term');
            $includeInactive = $request->get('include_inactive', false);
            $limit = $request->get('limit', 10);

            if (!$searchTerm) {
                return Response::error('Search term is required');
            }

            $query = User::with(['roles', 'team', 'designation', 'branch'])
                ->search($searchTerm);

            if (!$includeInactive) {
                $query->active();
            }

            $users = $query->limit($limit)->get();

            $responseData = [
                'success' => true,
                'message' => "Found {$users->count()} user(s) matching '{$searchTerm}'",
                'total_results' => $users->count(),
                'users' => $users->map(function ($user) {
                    return [
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
                                'description' => $role->description,
                            ];
                        }),
                    ];
                }),
            ];

            if ($users->isEmpty()) {
                $responseData['suggestions'] = [
                    'Try a different search term',
                    'Check if the user might be inactive (use include_inactive: true)',
                    'Verify the spelling of the search term',
                ];
            }

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to search users: ' . $e->getMessage());
        }
    }
}
