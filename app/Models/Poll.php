<?php
/*
ce modele Eloquent va faire du coup le lien entre la table poll
et base de données et le code php, il faut assi les relations avec les 
autres tables
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    /*
    liste des champs autorisés a etre remplis pour un poll
    on declare avec $fillable pour dire quels champs peuvent etre passes
    */
    protected $fillable = [
        /*
        et du coup la les champs qu on avait aussi dans la migration
        avec genre le titre optionne, la question obligatoire etc...
        */
        'title',
        'question',
        'secret_token',
        'is_draft',
        'allow_multiple_choices',
        'allow_vote_change',
        'results_public',
        'duration',
        'started_at',
        'ends_at',
    ];

    /* 
    du coup la il y a ensuire les relations avec les autres tables et tout
    */

    /*
    genre la un sondage appartien a un seul utilisateur
    */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    la un sondage a plusieurs options de reponse
    */
    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    /*
    un sondage a du coup plusieurs votes
    */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}
