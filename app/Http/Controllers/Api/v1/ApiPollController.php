<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiPollController extends Controller
{
    /**
     * Liste les sondages de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $polls = $request->user()
            ->polls()
            ->withCount('votes')
            ->orderBy('created_at', 'desc')
            ->get();

        return $polls;
    }

    /**
     * Crée un nouveau sondage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question'               => 'required|string|max:500',
            'title'                  => 'nullable|string|max:255',
            'allow_multiple_choices' => 'boolean',
            'allow_vote_change'      => 'boolean',
            'results_public'         => 'boolean',
            'duration'               => 'nullable|integer|min:1',
            'start_now'              => 'boolean',
        ]);

        $poll = new Poll();
        $poll->question               = $validated['question'];
        $poll->title                  = $validated['title'] ?? null;
        $poll->allow_multiple_choices = $validated['allow_multiple_choices'] ?? false;
        $poll->allow_vote_change      = $validated['allow_vote_change'] ?? false;
        $poll->results_public         = $validated['results_public'] ?? false;
        $poll->duration               = $validated['duration'] ?? null;
        $poll->secret_token           = Str::random(32);
        $poll->is_draft               = true;
        $poll->user()->associate($request->user());

        if (!empty($validated['start_now'])) {
            $poll->is_draft   = false;
            $poll->started_at = now();
            $poll->ends_at    = $poll->duration ? now()->addSeconds($poll->duration) : null;
        }

        $poll->save();

        return response()->json($poll->load('options'), 201);
    }

    /**
     * Affiche un sondage via son token secret.
     * Route publique — tout le monde peut y accéder.
     */
    public function show(Request $request, string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Sondage introuvable.'], 404);
        }

        $user    = $request->user();
        $isOwner = $user && $user->id === $poll->user_id;

        $hasVoted         = false;
        $userVoteOptionIds = [];

        if ($user) {
            $userVotes         = $poll->votes()->where('user_id', $user->id)->get();
            $hasVoted          = $userVotes->isNotEmpty();
            $userVoteOptionIds = $userVotes->pluck('poll_option_id')->toArray();
        }

        $isExpired = $poll->ends_at && now()->isAfter($poll->ends_at);

        return response()->json([
            'poll'                => $poll,
            'is_owner'            => $isOwner,
            'has_voted'           => $hasVoted,
            'user_vote_option_ids' => $userVoteOptionIds,
            'is_expired'          => $isExpired,
            'is_authenticated'    => (bool) $user,
        ]);
    }

    /**
     * Met à jour un sondage (réservé au propriétaire).
     */
    public function update(Request $request, string $id)
    {
        $poll = Poll::findOrFail($id);

        if ($poll->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validated = $request->validate([
            'question'               => 'sometimes|required|string|max:500',
            'title'                  => 'nullable|string|max:255',
            'allow_multiple_choices' => 'boolean',
            'allow_vote_change'      => 'boolean',
            'results_public'         => 'boolean',
            'duration'               => 'nullable|integer|min:1',
        ]);

        $poll->fill($validated);
        $poll->save();

        return $poll->load('options');
    }

    /**
     * Supprime un sondage (réservé au propriétaire).
     */
    public function destroy(Request $request, string $id)
    {
        $poll = Poll::findOrFail($id);

        if ($poll->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $poll->delete();

        return response()->noContent();
    }

    /**
     * Lance un sondage en brouillon.
     */
    public function start(Request $request, string $id)
    {
        $poll = Poll::findOrFail($id);

        if ($poll->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        if (!$poll->is_draft) {
            return response()->json(['message' => 'Ce sondage est déjà lancé.'], 422);
        }

        $poll->is_draft   = false;
        $poll->started_at = now();
        $poll->ends_at    = $poll->duration ? now()->addSeconds($poll->duration) : null;
        $poll->save();

        return $poll->load('options');
    }

    /**
     * Ajoute une option à un sondage.
     */
    public function storeOption(Request $request, string $id)
    {
        $poll = Poll::findOrFail($id);

        if ($poll->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $option = new PollOption();
        $option->label = $validated['label'];
        $option->poll()->associate($poll);
        $option->save();

        return response()->json($option, 201);
    }

    /**
     * Modifie une option d'un sondage.
     */
    public function updateOption(Request $request, string $id, string $optionId)
    {
        $poll   = Poll::findOrFail($id);
        $option = PollOption::findOrFail($optionId);

        if ($poll->user_id !== $request->user()->id || $option->poll_id !== $poll->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validated = $request->validate([
            'label' => 'required|string|max:255',
        ]);

        $option->label = $validated['label'];
        $option->save();

        return $option;
    }

    /**
     * Supprime une option d'un sondage.
     */
    public function destroyOption(Request $request, string $id, string $optionId)
    {
        $poll   = Poll::findOrFail($id);
        $option = PollOption::findOrFail($optionId);

        if ($poll->user_id !== $request->user()->id || $option->poll_id !== $poll->id) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $option->delete();

        return response()->noContent();
    }

    /**
     * Soumet un vote pour un sondage.
     */
    public function vote(Request $request, string $token)
    {
        $poll = Poll::with('options')->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Sondage introuvable.'], 404);
        }

        if ($poll->is_draft) {
            return response()->json(['message' => 'Ce sondage n\'est pas encore lancé.'], 422);
        }

        if ($poll->ends_at && now()->isAfter($poll->ends_at)) {
            return response()->json(['message' => 'Ce sondage est terminé.'], 422);
        }

        $validated = $request->validate([
            'option_ids'   => 'required|array|min:1',
            'option_ids.*' => 'integer|exists:poll_options,id',
        ]);

        $user      = $request->user();
        $optionIds = $validated['option_ids'];

        // Vérifier que toutes les options appartiennent à ce sondage
        $validOptionIds = $poll->options->pluck('id')->toArray();
        foreach ($optionIds as $optId) {
            if (!in_array($optId, $validOptionIds)) {
                return response()->json(['message' => 'Option invalide.'], 422);
            }
        }

        // Forcer le choix unique si nécessaire
        if (!$poll->allow_multiple_choices && count($optionIds) > 1) {
            return response()->json(['message' => 'Ce sondage n\'accepte qu\'un seul choix.'], 422);
        }

        $existingVotes = $poll->votes()->where('user_id', $user->id)->get();

        if ($existingVotes->isNotEmpty()) {
            if (!$poll->allow_vote_change) {
                return response()->json(['message' => 'Vous avez déjà voté pour ce sondage.'], 422);
            }
            // Changement de vote autorisé : on supprime les anciens votes
            $poll->votes()->where('user_id', $user->id)->delete();
        }

        foreach ($optionIds as $optId) {
            $poll->votes()->create([
                'user_id'        => $user->id,
                'poll_option_id' => $optId,
            ]);
        }

        return response()->json(['message' => 'Vote enregistré.'], 201);
    }

    /**
     * Retourne les résultats en direct d'un sondage.
     */
    public function results(Request $request, string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Sondage introuvable.'], 404);
        }

        $user = $request->user();

        if (!$poll->results_public && !$user) {
            return response()->json(['message' => 'Résultats non publics.'], 403);
        }

        $totalVotes = $poll->votes()->count();

        $options = $poll->options->map(function ($option) use ($totalVotes) {
            return [
                'id'         => $option->id,
                'label'      => $option->label,
                'votes'      => $option->votes_count,
                'percentage' => $totalVotes > 0
                    ? round(($option->votes_count / $totalVotes) * 100, 1)
                    : 0,
            ];
        });

        return response()->json([
            'total_votes' => $totalVotes,
            'options'     => $options,
        ]);
    }
}
