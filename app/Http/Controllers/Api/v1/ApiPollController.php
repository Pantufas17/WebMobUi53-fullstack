<?php
/*
cest le controlleur principal, il recoit les requettes HTTP du frontend
effectue la logique metier (Crer, modifier, supprimier,...) un vote
je refait limite l entierte du fichier car la base avait l index
et show qui retournait juste le sondage brut
du coup manquait bien update, destroy et vote et tout
*/

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ApiPollController extends Controller
{
    /*
    retoure la liste de tous les sondages du user connecté
    on charge aussi avec les options 
    */
    public function index(Request $request)
    {
        return $request->user()->polls()
            ->with('options')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /*
    store pour creer une nouvelle poll avec ces options en validant
    toutes les donnes envoyés par le front et puis on creer le sondage
    et ses options en base de données.
    */
    public function store(Request $request)
    {
        /*
        on valide toutes les données recues du front et voir si elles respectent
        bien toutes les contraintre mise en place genre required, type string avec max ou min
        */

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

        /*
        on creer du coup le sondage via la relation avec polls() du user connecté
        */

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

        /*
        on creer chaque option de reponse liee a ce sondage apres ces eloquent 
        qui va gerer automoatiquement le poll_id grace a la relation avec options()
        */
        foreach ($validated['options'] as $label) {
            $poll->options()->create(['label' => $label]);
        }

        /*
        et pour la fin du coup on retourne le sondage avec ces options
        */
        return $poll->load('options');
    }

    /*
    du coup pour modifier / mettre a jour un sondage existant
    laravel cherche un poll avec cet id correspondant et va le passer directement
    en parametre
    */

    public function update(Request $request, Poll $poll)
    {
        /*
        verification d'autorisation pour bien etre sur que nous sommmes 
        la personne qui a crée le poll et que nous avons bien acces a la modif
        du sondage. On compare du coup les id, si cest bon , il peut sinon
        on lui mets un message de comme quoi il a pas le droit avec erreur 403
        */

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

        /*on remplace toutes les options du coup par les nouvelles données .
        genre on supp toutes les anciennes valeurs par les nouvelles 
        plus simple que faire a chaque fois la difference entre l encienne et la nouvelle
        et savoir si du coup faut mettre a jour ou pas.
        */
        $poll->options()->delete();
        foreach ($validated['options'] as $label) {
            $poll->options()->create(['label' => $label]);
        }

        return $poll->load('options');
    }

    /*
    la fonction show qui avait de base mais du coup mtn elle n affiche plus le truc en brut.
    du coup elle va afficher un sondage via le token secret (genre dans l url accessible par tous,
    pas besoin d etre connecté pour acceder et voir le sondage).

    elle affiche aussi du coup avec le sondages, les options mais aussi si on peut voter ou pas.
    */
    public function show(Request $request, string $token)
    {
        /*
        on vient chercher du coup le sondage avec son token secret
        et on charge les options avec leur nombre de votes pour les resultats.
        */
        $poll = Poll::with(['options' => function ($query) {
            //ici avec le compte pour chaque option
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        $user = $request->user();
        //null si annonyme
        $userVoteOptionIds = [];

        /*
        du coup si le user est conncté on recupere les ids des options
        pour lequelles il a deja voté et du coup ca detecte sil a deja vote ou pas.
        */
        if ($user) {
            $userVoteOptionIds = $poll->votes()
                ->where('user_id', $user->id)
                ->pluck('poll_option_id')
                ->toArray();
        }

        /*
        verifies du coup si le user peut voter ou pas
        sil est connecte il peut voter, si le poll nest pas mode draft
        on peut voter, et si le sondage nest pas terminer on peut aussi voter.
        du coup ces 3 regles.
        */
        $canVote = $user
            && !$poll->is_draft
            && ($poll->ends_at === null || now()->lt($poll->ends_at));

        return response()->json([
            'poll'                 => $poll,
            'user_vote_option_ids' => $userVoteOptionIds,
            'can_vote'             => $canVote,
        ]);
    }

    /*
    du coup la fonction delete pour supp un sondage.
    tout comme pour update, on verifie bien que le user en question cest le 
    bon user et qu il a en effet le droit de supprimer le poll
    et du coup grace au cascade defini dans la migration au debut toutes les 
    options et votes qui sont liée au id du poll en question, sont egalement
    supp automatiquement de la bd.
    */
    public function destroy(Request $request, Poll $poll)
    {
        if ($poll->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $poll->delete();

        return response()->json(['message' => 'Poll deleted.']);
    }

    /*
    la fonction vote pour enregistrer un vote dun user pour un sondage
    on utilise encore le token secret et pas le id tout comme pour la fonction show
    pour que la meme url puisse entre la meme pour afficher et voter 
    */
    public function vote(Request $request, string $token)
    {
        $poll = Poll::where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        /*
        on verfie que le sondage est bien actif
        genre que cest pas un brouillon et quil nest pas expiré
        */
        if ($poll->is_draft) {
            return response()->json(['message' => 'Ce sondage n\'est pas encore ouvert.'], 403);
        }

        if ($poll->ends_at && now()->gt($poll->ends_at)) {
            return response()->json(['message' => 'Ce sondage est terminé.'], 403);
        }

        /*
        on valide les options choisis par le user
        'exists:poll_options,id' pour verfier que chaque id existe bien
        et qu on vote pour une option qui existe en lien avec ce sondage et pas un autre
        sondage ailleurs.
        */

        $validated = $request->validate([
            'option_ids'   => 'required|array|min:1',
            'option_ids.*' => 'exists:poll_options,id',
        ]);

        $user = $request->user();

        /*
        du coup pour la verification du vote et voir si le user a deja voté, et
        si cest le cas qu il puisse le faire une seule fois et pas truqué les votes
        */
        $hasVoted = $poll->votes()->where('user_id', $user->id)->exists();
        if ($hasVoted && !$poll->allow_vote_change) {
            return response()->json(['message' => 'Vous avez déjà voté.'], 403);
        }

        /*
        la contrainte du choix multiple si le sondage est en choix simple 
        on verifie que le front n as envoyé que une suele options
        avec ce message que du coup au cas ou pour preciser que le sondage en question
        permet que un seul choix.
        */
        if (!$poll->allow_multiple_choices && count($validated['option_ids']) > 1) {
            return response()->json(['message' => 'Ce sondage ne permet qu\'un seul choix.'], 422);
        }


        /*
        on enregistre le vote dans une transaction DB pour du coup
        supprimer les anciens votes de cet user et inserer les nouveaux votes
        et si jamais un truc plante entre temps, la transaction annule tout.
        et sans transaction aussi, on pourrait se retrouver avec 0 votes alors
        que le user avait voté

        */
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
