<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ApiPollController extends Controller
{
    /**
     * Display a listing of the authenticated user's polls.
     */
    public function index(Request $request)
    {
        return $request->user()->polls()
            ->with('options')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Store a new poll.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'                  => 'nullable|string|max:255',
            'question'               => 'required|string|max:255',
            'options'                => 'required|array|min:2',
            'options.*'              => 'required|string|max:255',
            'is_draft'               => 'boolean',
            'allow_multiple_choices' => 'boolean',
            'results_public'         => 'boolean',
            'allow_vote_change'      => 'boolean',
            'duration'               => 'nullable|integer|min:1',
        ]);

        $poll = $request->user()->polls()->create([
            'title'                  => $validated['title'] ?? null,
            'question'               => $validated['question'],
            'secret_token'           => Str::random(32),
            'is_draft'               => $validated['is_draft'] ?? true,
            'allow_multiple_choices' => $validated['allow_multiple_choices'] ?? false,
            'results_public'         => $validated['results_public'] ?? false,
            'allow_vote_change'      => $validated['allow_vote_change'] ?? false,
            'duration'               => $validated['duration'] ?? null,
            'starts_at'              => now(),
            'ends_at'                => $validated['duration'] ? now()->addSeconds((int)$validated['duration']) : null,
        ]);

        foreach ($validated['options'] as $label) {
            $poll->options()->create(['label' => $label]);
        }

        return $poll->load('options');
    }

    /**
     * Update an existing poll.
     */
    public function update(Request $request, Poll $poll)
    {
        if ($poll->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'title'                  => 'nullable|string|max:255',
            'question'               => 'required|string|max:255',
            'options'                => 'required|array|min:2',
            'options.*'              => 'required|string|max:255',
            'is_draft'               => 'boolean',
            'allow_multiple_choices' => 'boolean',
            'results_public'         => 'boolean',
            'allow_vote_change'      => 'boolean',
            'duration'               => 'nullable|integer|min:1',
        ]);

        $poll->update([
            'title'                  => $validated['title'] ?? null,
            'question'               => $validated['question'],
            'is_draft'               => $validated['is_draft'] ?? $poll->is_draft,
            'allow_multiple_choices' => $validated['allow_multiple_choices'] ?? $poll->allow_multiple_choices,
            'results_public'         => $validated['results_public'] ?? $poll->results_public,
            'allow_vote_change'      => $validated['allow_vote_change'] ?? $poll->allow_vote_change,
            'duration'               => $validated['duration'] ?? null,
            'ends_at'                => $validated['duration'] ? now()->addSeconds((int)$validated['duration']) : null,
        ]);

        // Remplace toutes les options
        $poll->options()->delete();
        foreach ($validated['options'] as $label) {
            $poll->options()->create(['label' => $label]);
        }

        return $poll->load('options');
    }

    /**
     * Display the specified poll by its secret token.
     */
    public function show(Request $request, string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $user = $request->user();
        $userVoteOptionIds = [];

        if ($user) {
            $userVoteOptionIds = $poll->votes()
                ->where('user_id', $user->id)
                ->pluck('poll_option_id')
                ->toArray();
        }

        $canVote = $user
            && !$poll->is_draft
            && ($poll->ends_at === null || now()->lt($poll->ends_at));

        return response()->json([
            'poll'                 => $poll,
            'user_vote_option_ids' => $userVoteOptionIds,
            'can_vote'             => $canVote,
        ]);
    }

    /**
     * Delete a poll.
     */
    public function destroy(Request $request, Poll $poll)
    {
        if ($poll->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $poll->delete();

        return response()->json(['message' => 'Poll deleted.']);
    }

    /**
     * Cast a vote.
     */
    public function vote(Request $request, string $token)
    {
        $poll = Poll::where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        if ($poll->is_draft) {
            return response()->json(['message' => 'Ce sondage n\'est pas encore ouvert.'], 403);
        }

        if ($poll->ends_at && now()->gt($poll->ends_at)) {
            return response()->json(['message' => 'Ce sondage est terminé.'], 403);
        }

        $validated = $request->validate([
            'option_ids'   => 'required|array|min:1',
            'option_ids.*' => 'exists:poll_options,id',
        ]);

        $user = $request->user();

        // Vérifier si le user a déjà voté
        $hasVoted = $poll->votes()->where('user_id', $user->id)->exists();
        if ($hasVoted && !$poll->allow_vote_change) {
            return response()->json(['message' => 'Vous avez déjà voté.'], 403);
        }

        // Check contrainte choix multiple
        if (!$poll->allow_multiple_choices && count($validated['option_ids']) > 1) {
            return response()->json(['message' => 'Ce sondage ne permet qu\'un seul choix.'], 422);
        }

        DB::transaction(function () use ($poll, $user, $validated) {
            $poll->votes()->where('user_id', $user->id)->delete();

            foreach ($validated['option_ids'] as $optionId) {
                $poll->votes()->create([
                    'user_id'        => $user->id,
                    'poll_option_id' => $optionId,
                ]);
            }
        });

        return response()->json(['message' => 'Vote enregistré !']);
    }
}
