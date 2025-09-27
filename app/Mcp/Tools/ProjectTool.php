<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Models\ParentProject;
use App\Models\User;
use App\Models\Team;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ProjectTool extends Tool
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
            'properties' => [
                'project_id' => [
                    'type' => 'integer',
                    'description' => 'Get specific project by ID',
                ],
                'project_name' => [
                    'type' => 'string',
                    'description' => 'Search projects by name',
                ],
                'parent_project_id' => [
                    'type' => 'integer',
                    'description' => 'Get projects under specific parent project',
                ],
                'parent_project_name' => [
                    'type' => 'string',
                    'description' => 'Get projects under parent project by name',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Filter by project status',
                    'enum' => ['active', 'completed', 'on_hold', 'cancelled'],
                ],
                'project_owner_id' => [
                    'type' => 'integer',
                    'description' => 'Get projects owned by specific user',
                ],
                'project_manager_id' => [
                    'type' => 'integer',
                    'description' => 'Get projects managed by specific user',
                ],
                'include_resources' => [
                    'type' => 'boolean',
                    'description' => 'Include resource allocation details',
                    'default' => false,
                ],
                'include_hierarchy' => [
                    'type' => 'boolean',
                    'description' => 'Include parent-child project relationships',
                    'default' => false,
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results (default: 50)',
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
            $projectId = $request->get('project_id');
            $projectName = $request->get('project_name');
            $parentProjectId = $request->get('parent_project_id');
            $parentProjectName = $request->get('parent_project_name');
            $status = $request->get('status');
            $projectOwnerId = $request->get('project_owner_id');
            $projectManagerId = $request->get('project_manager_id');
            $includeResources = $request->get('include_resources', false);
            $includeHierarchy = $request->get('include_hierarchy', false);
            $limit = $request->get('limit', 50);

            // Build the query
            $query = Project::with(['projectOwner', 'projectManager', 'parentProject']);

            // Apply filters
            if ($projectId) {
                $query->where('id', $projectId);
            }

            if ($projectName) {
                $query->where('name', 'like', "%{$projectName}%");
            }

            if ($parentProjectName && !$parentProjectId) {
                $parentProject = ParentProject::where('name', 'like', "%{$parentProjectName}%")->first();
                if (!$parentProject) {
                    return Response::error("Parent project with name '{$parentProjectName}' not found");
                }
                $parentProjectId = $parentProject->id;
            }

            if ($parentProjectId) {
                $query->where('parent_project_id', $parentProjectId);
            }

            if ($status) {
                $query->byStatus($status);
            }

            if ($projectOwnerId) {
                $query->where('project_owner_id', $projectOwnerId);
            }

            if ($projectManagerId) {
                $query->where('project_manager_id', $projectManagerId);
            }

            // Order by name
            $query->orderBy('name');

            // Execute the query with limit
            $projects = $query->limit($limit)->get();

            // Format the results
            $results = $projects->map(function ($project) use ($includeResources, $includeHierarchy) {
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

                if ($includeHierarchy && $project->parentProject) {
                    $data['parent_project'] = [
                        'id' => $project->parentProject->id,
                        'name' => $project->parentProject->name,
                        'status' => $project->parentProject->status,
                    ];
                }

                if ($includeResources) {
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
                }

                return $data;
            });

            // Calculate summary statistics
            $summary = [
                'total_projects' => $projects->count(),
                'by_status' => $projects->groupBy('status')->map->count(),
                'by_billing_frequency' => $projects->groupBy('billing_frequency')->map->count(),
                'active_projects' => $projects->where('status', 'active')->count(),
                'completed_projects' => $projects->where('status', 'completed')->count(),
            ];

            if ($includeResources) {
                $summary['total_resources'] = $projects->sum(function ($project) {
                    return $project->projectResources()->count();
                });
                $summary['primary_projects'] = $projects->sum(function ($project) {
                    return $project->projectResources()->where('primary_project', true)->count();
                });
            }

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Retrieved {$results->count()} project(s)",
                'filters_applied' => array_filter([
                    'project_id' => $projectId,
                    'project_name' => $projectName,
                    'parent_project_id' => $parentProjectId,
                    'parent_project_name' => $parentProjectName,
                    'status' => $status,
                    'project_owner_id' => $projectOwnerId,
                    'project_manager_id' => $projectManagerId,
                ]),
                'summary' => $summary,
                'projects' => $results->toArray(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve project data: ' . $e->getMessage());
        }
    }
}
