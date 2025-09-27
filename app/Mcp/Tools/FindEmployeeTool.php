<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class FindEmployeeTool extends Tool
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
            'properties' => [
                'search_term' => [
                    'type' => 'string',
                    'description' => 'The name, employee ID, or email to search for',
                ],
                'include_inactive' => [
                    'type' => 'boolean',
                    'description' => 'Whether to include inactive employees in the search (default: false)',
                    'default' => false,
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results to return (default: 10)',
                    'default' => 10,
                    'minimum' => 1,
                    'maximum' => 50,
                ],
            ],
            'required' => ['search_term'],
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        try {
            $searchTerm = $request->get('search_term');
            $includeInactive = $request->get('include_inactive', false);
            $limit = $request->get('limit', 10);

            // Validate search term
            if (empty(trim($searchTerm))) {
                return Response::error('Search term cannot be empty');
            }

            // Build the query
            $query = User::with(['roles', 'team', 'designation', 'branch'])
                ->search($searchTerm);

            // Filter out inactive employees unless specifically requested
            if (!$includeInactive) {
                $query->active();
            }

            // Execute the query with limit
            $employees = $query->limit($limit)->get();

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

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Found {$results->count()} employee(s) matching '{$searchTerm}'",
                'total_results' => $results->count(),
                'employees' => $results->toArray(),
            ];

            // Add search suggestions if no results found
            if ($results->isEmpty()) {
                $responseData['suggestions'] = [
                    'Try searching with a different name or employee ID',
                    'Check if the employee might be inactive (use include_inactive: true)',
                    'Verify the spelling of the search term',
                ];
            }

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to search employees: ' . $e->getMessage());
        }
    }
}
