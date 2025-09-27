<?php

namespace App\Mcp\Tools;

use App\Models\Card;
use App\Models\User;
use App\Models\Project;
use App\Models\Team;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CardsTool extends Tool
{
    /**
     * The tool's name.
     */
    public function name(): string
    {
        return 'cards_data';
    }

    /**
     * The tool's description.
     */
    public function description(): string
    {
        return 'Get recognition cards (Green Cards) information including issuance tracking, recipients, and team recognition analytics.';
    }

    /**
     * The tool's input schema.
     */
    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'card_id' => [
                    'type' => 'integer',
                    'description' => 'Get specific card by ID',
                ],
                'type' => [
                    'type' => 'string',
                    'description' => 'Filter by card type (e.g., Green Card)',
                ],
                'suggestion_status' => [
                    'type' => 'string',
                    'description' => 'Filter by suggestion status',
                    'enum' => ['pending', 'approved', 'rejected'],
                ],
                'issuing_status' => [
                    'type' => 'string',
                    'description' => 'Filter by issuing status',
                    'enum' => ['pending', 'issued', 'rejected'],
                ],
                'suggested_by' => [
                    'type' => 'integer',
                    'description' => 'Get cards suggested by specific user',
                ],
                'issued_by' => [
                    'type' => 'integer',
                    'description' => 'Get cards issued by specific user',
                ],
                'recipient_id' => [
                    'type' => 'integer',
                    'description' => 'Get cards received by specific user',
                ],
                'recipient_name' => [
                    'type' => 'string',
                    'description' => 'Get cards received by user name',
                ],
                'project_id' => [
                    'type' => 'integer',
                    'description' => 'Get cards for specific project',
                ],
                'project_name' => [
                    'type' => 'string',
                    'description' => 'Get cards for project by name',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Get cards for specific team',
                ],
                'team_name' => [
                    'type' => 'string',
                    'description' => 'Get cards for team by name',
                ],
                'start_date' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Filter cards issued from this date (YYYY-MM-DD)',
                ],
                'end_date' => [
                    'type' => 'string',
                    'format' => 'date',
                    'description' => 'Filter cards issued until this date (YYYY-MM-DD)',
                ],
                'year' => [
                    'type' => 'integer',
                    'description' => 'Filter by specific year',
                ],
                'month' => [
                    'type' => 'integer',
                    'description' => 'Filter by specific month (1-12)',
                ],
                'include_recipients' => [
                    'type' => 'boolean',
                    'description' => 'Include detailed recipient information',
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
            $cardId = $request->get('card_id');
            $type = $request->get('type');
            $suggestionStatus = $request->get('suggestion_status');
            $issuingStatus = $request->get('issuing_status');
            $suggestedBy = $request->get('suggested_by');
            $issuedBy = $request->get('issued_by');
            $recipientId = $request->get('recipient_id');
            $recipientName = $request->get('recipient_name');
            $projectId = $request->get('project_id');
            $projectName = $request->get('project_name');
            $teamId = $request->get('team_id');
            $teamName = $request->get('team_name');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $year = $request->get('year');
            $month = $request->get('month');
            $includeRecipients = $request->get('include_recipients', false);
            $limit = $request->get('limit', 50);

            // Build the query
            $query = Card::with(['project', 'suggestedBy', 'issuedBy']);

            // Apply filters
            if ($cardId) {
                $query->where('id', $cardId);
            }

            if ($type) {
                $query->byType($type);
            }

            if ($suggestionStatus) {
                $query->bySuggestionStatus($suggestionStatus);
            }

            if ($issuingStatus) {
                $query->byIssuingStatus($issuingStatus);
            }

            if ($suggestedBy) {
                $query->where('suggested_id', $suggestedBy);
            }

            if ($issuedBy) {
                $query->where('issuer_id', $issuedBy);
            }

            // Apply recipient filter
            if ($recipientName && !$recipientId) {
                $user = User::where('name', 'like', "%{$recipientName}%")->first();
                if (!$user) {
                    return Response::error("User with name '{$recipientName}' not found");
                }
                $recipientId = $user->id;
            }

            if ($recipientId) {
                $query->whereHas('users', function ($q) use ($recipientId) {
                    $q->where('user_id', $recipientId);
                });
            }

            // Apply project filter
            if ($projectName && !$projectId) {
                $project = Project::where('name', 'like', "%{$projectName}%")->first();
                if (!$project) {
                    return Response::error("Project with name '{$projectName}' not found");
                }
                $projectId = $project->id;
            }

            if ($projectId) {
                $query->where('project_id', $projectId);
            }

            // Apply team filter
            if ($teamName && !$teamId) {
                $team = Team::where('name', 'like', "%{$teamName}%")->first();
                if (!$team) {
                    return Response::error("Team with name '{$teamName}' not found");
                }
                $teamId = $team->id;
            }

            if ($teamId) {
                $query->whereHas('users', function ($q) use ($teamId) {
                    $q->whereHas('team', function ($subQ) use ($teamId) {
                        $subQ->where('id', $teamId);
                    });
                });
            }

            // Apply date filters
            if ($startDate && $endDate) {
                $query->byDateRange($startDate, $endDate);
            } elseif ($year && $month) {
                $query->whereYear('issued_at', $year)->whereMonth('issued_at', $month);
            } elseif ($year) {
                $query->whereYear('issued_at', $year);
            }

            // Order by issued date (most recent first)
            $query->orderBy('issued_at', 'desc');

            // Execute the query with limit
            $cards = $query->limit($limit)->get();

            // Format the results
            $results = $cards->map(function ($card) use ($includeRecipients) {
                $data = [
                    'id' => $card->id,
                    'type' => $card->type,
                    'job_description' => $card->job_description,
                    'content' => $card->content,
                    'suggestion_status' => $card->suggestion_status,
                    'issuing_status' => $card->issuing_status,
                    'suggested_at' => $card->suggested_at?->format('Y-m-d'),
                    'issued_at' => $card->issued_at?->format('Y-m-d'),
                    'rejected_reason' => $card->rejected_reason,
                    'delay_reason' => $card->delay_reason,
                    'reminder_date' => $card->reminder_date?->format('Y-m-d'),
                    'escalation_date' => $card->escalation_date?->format('Y-m-d'),
                    'project' => $card->project ? [
                        'id' => $card->project->id,
                        'name' => $card->project->name,
                        'status' => $card->project->status,
                    ] : null,
                    'suggested_by' => $card->suggestedBy ? [
                        'id' => $card->suggestedBy->id,
                        'name' => $card->suggestedBy->name,
                        'email' => $card->suggestedBy->email,
                    ] : null,
                    'issued_by' => $card->issuedBy ? [
                        'id' => $card->issuedBy->id,
                        'name' => $card->issuedBy->name,
                        'email' => $card->issuedBy->email,
                    ] : null,
                ];

                if ($includeRecipients) {
                    $data['recipients'] = $card->users()->with(['team', 'designation'])->get()->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'employee_id' => $user->employee_id,
                            'email' => $user->email,
                            'team' => $user->team ? [
                                'id' => $user->team->id,
                                'name' => $user->team->name,
                            ] : null,
                            'designation' => $user->designation ? [
                                'id' => $user->designation->id,
                                'name' => $user->designation->name,
                            ] : null,
                        ];
                    });
                }

                return $data;
            });

            // Calculate summary statistics
            $summary = [
                'total_cards' => $cards->count(),
                'by_type' => $cards->groupBy('type')->map->count(),
                'by_suggestion_status' => $cards->groupBy('suggestion_status')->map->count(),
                'by_issuing_status' => $cards->groupBy('issuing_status')->map->count(),
                'by_project' => $cards->groupBy('project.name')->map->count(),
                'issued_this_month' => $cards->where('issued_at', '>=', now()->startOfMonth())->count(),
                'issued_this_year' => $cards->where('issued_at', '>=', now()->startOfYear())->count(),
            ];

            if ($includeRecipients) {
                $summary['total_recipients'] = $cards->sum(function ($card) {
                    return $card->users()->count();
                });
                $summary['by_team'] = $cards->flatMap(function ($card) {
                    return $card->users()->with('team')->get()->pluck('team.name');
                })->filter()->groupBy(function ($teamName) {
                    return $teamName;
                })->map->count();
            }

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Retrieved {$results->count()} card(s)",
                'filters_applied' => array_filter([
                    'card_id' => $cardId,
                    'type' => $type,
                    'suggestion_status' => $suggestionStatus,
                    'issuing_status' => $issuingStatus,
                    'suggested_by' => $suggestedBy,
                    'issued_by' => $issuedBy,
                    'recipient_id' => $recipientId,
                    'recipient_name' => $recipientName,
                    'project_id' => $projectId,
                    'project_name' => $projectName,
                    'team_id' => $teamId,
                    'team_name' => $teamName,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'year' => $year,
                    'month' => $month,
                ]),
                'summary' => $summary,
                'cards' => $results->toArray(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve cards data: ' . $e->getMessage());
        }
    }
}
