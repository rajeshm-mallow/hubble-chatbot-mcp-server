<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Models\ParentProject;
use App\Models\User;
use App\Models\Team;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class ProjectTool extends BaseTool
{
    /**
     * The tool's name.
     */
    public function name(): string
    {
        return 'project_data';
    }

    /**
     * The tool's description.
     */
    public function description(): string
    {
        return 'Get project information including details, resource allocation, parent-child relationships, and project hierarchy.';
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

            // Get projects where user is owner or manager
            $query = Project::with(['projectOwner', 'projectManager', 'parentProject'])
                ->where(function ($q) use ($user) {
                    $q->where('project_owner_id', $user->id)
                      ->orWhere('project_manager_id', $user->id);
                });

            // Order by name
            $query->orderBy('name');

            // Execute the query
            $projects = $query->get();

            // Format the results
            $results = $projects->map(function ($project) {
                $data = [
                    'id' => $project->id,
                    'project_id' => $project->project_id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'version' => $project->version,
                    'billing_frequency' => $project->billing_frequency,
                    'project_end_date' => $project->project_end_date?->format('Y-m-d'),
                    'project_owner' => $project->projectOwner ? [
                        'id' => $project->projectOwner->id,
                        'name' => $project->projectOwner->name,
                        'email' => $project->projectOwner->email,
                    ] : null,
                    'project_manager' => $project->projectManager ? [
                        'id' => $project->projectManager->id,
                        'name' => $project->projectManager->name,
                        'email' => $project->projectManager->email,
                    ] : null,
                    'created_at' => $project->created_at->format('Y-m-d H:i:s'),
                ];

                if ($project->parentProject) {
                    $data['parent_project'] = [
                        'id' => $project->parentProject->id,
                        'name' => $project->parentProject->name,
                        'status' => $project->parentProject->status,
                    ];
                }

                // Include resources for user's projects
                $data['resources'] = $project->projectResources()->with(['user', 'reportingPerson', 'designation'])->get()->map(function ($resource) {
                    return [
                        'id' => $resource->id,
                        'user' => $resource->user ? [
                            'id' => $resource->user->id,
                            'name' => $resource->user->name,
                            'employee_id' => $resource->user->employee_id,
                        ] : null,
                        'resource_type' => $resource->resource_type,
                        'utilisation' => $resource->utilisation,
                        'charge_by_hour' => $resource->charge_by_hour,
                        'primary_project' => $resource->primary_project,
                        'project_owner' => $resource->project_owner,
                        'allotted_from' => $resource->allotted_from?->format('Y-m-d'),
                        'allotted_to' => $resource->allotted_to?->format('Y-m-d'),
                        'reporting_person' => $resource->reportingPerson ? [
                            'id' => $resource->reportingPerson->id,
                            'name' => $resource->reportingPerson->name,
                        ] : null,
                        'designation' => $resource->designation ? [
                            'id' => $resource->designation->id,
                            'name' => $resource->designation->name,
                        ] : null,
                    ];
                });

                return $data;
            });

            // Calculate summary statistics
            $summary = [
                'total_projects' => $projects->count(),
                'by_status' => $projects->groupBy('status')->map->count(),
                'by_billing_frequency' => $projects->groupBy('billing_frequency')->map->count(),
                'active_projects' => $projects->where('status', 'active')->count(),
                'completed_projects' => $projects->where('status', 'completed')->count(),
                'total_resources' => $projects->sum(function ($project) {
                    return $project->projectResources()->count();
                }),
                'primary_projects' => $projects->sum(function ($project) {
                    return $project->projectResources()->where('primary_project', true)->count();
                }),
            ];

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Retrieved {$results->count()} project(s) for {$user->name}",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'team' => $user->team ? $user->team->name : null,
                    'designation' => $user->designation ? $user->designation->name : null,
                ],
                'summary' => $summary,
                'projects' => $results->toArray(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve project data: ' . $e->getMessage());
        }
    }
}
