<?php

namespace App\Mcp\Tools;

use App\Models\Card;
use App\Models\User;
use App\Models\Project;
use App\Models\Team;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class CardsTool extends BaseTool
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

            // Build the query for cards related to current user
            $query = Card::with(['project', 'suggestedBy', 'issuedBy'])
                ->where(function ($q) use ($user) {
                    $q->where('suggested_id', $user->id)
                      ->orWhere('issuer_id', $user->id)
                      ->orWhereHas('users', function ($subQ) use ($user) {
                          $subQ->where('user_id', $user->id);
                      });
                });

            // Order by issued date (most recent first)
            $query->orderBy('issued_at', 'desc');

            // Execute the query
            $cards = $query->get();

            // Format the results
            $results = $cards->map(function ($card) {
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

                // Include recipients for user's cards
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
                'total_recipients' => $cards->sum(function ($card) {
                    return $card->users()->count();
                }),
                'by_team' => $cards->flatMap(function ($card) {
                    return $card->users()->with('team')->get()->pluck('team.name');
                })->filter()->groupBy(function ($teamName) {
                    return $teamName;
                })->map->count(),
            ];

            // Prepare response data
            $responseData = [
                'success' => true,
                'message' => "Retrieved {$results->count()} card(s) for {$user->name}",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'team' => $user->team ? $user->team->name : null,
                    'designation' => $user->designation ? $user->designation->name : null,
                ],
                'summary' => $summary,
                'cards' => $results->toArray(),
            ];

            return Response::json($responseData);

        } catch (\Exception $e) {
            return Response::error('Failed to retrieve cards data: ' . $e->getMessage());
        }
    }
}
