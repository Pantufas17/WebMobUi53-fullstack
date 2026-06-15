<?php

/*
pareil que pour le poll.php
du coup celui la cest le modele pour les options de reponse d une poll
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/*
du coup le modele represente une option de reponse dans un sondage
avec chaque option avec un label de texte
*/

class PollOption extends Model
{
    /*
    du coup on met fillable pour le fameux label texte qui peut a etre rempli
    */
    protected $fillable = ['label'];

    /*
    ici tout pareil pour gerer les relations
    et du coup apres ca va faire le lien directe avec la collone poll_id
    */
    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    /*
    que une option peut du coup avoir plusieurs votes
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }
}
