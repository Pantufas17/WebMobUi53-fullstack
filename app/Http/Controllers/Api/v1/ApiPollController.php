<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiPollController extends Controller
{
    /**
     * Display a listing of the authenticated user's polls.
     */
    public function index(Request $request)
    {
        $polls = $request->user()->polls()->with('options')->orderBy('created_at', 'desc')->get();
        return $polls;
    }


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
            'duration'               => 'nullable|integer|min:1',
        ]);
        $poll = $request->user()->polls()->create([
            'title'                  => $validated['title'] ?? null,
            'question'               => $validated['question'],
            'secret_token'           => Str::random(32),
            'is_draft'               => $validated['is_draft'] ?? true,
            'allow_multiple_choices' => $validated['allow_multiple_choices'] ?? false,
            'results_public'         => $validated['results_public'] ?? false,
            'duration'               => $validated['duration'] ?? null,
        ]);
        foreach ($validated['options'] as $label) {
            $poll->options()->create(['label' => $label]);
        }
        return $poll->load('options');
    }

    /**
     * Display the specified poll by its secret token.
     */
    public function show(string $token)
    {
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->first();

        if (!$poll) {
            return response()->json(['message' => 'Poll not found.'], 404);
        }

        return $poll;
    }
}
