<?php
/*
du coup cest le modele pour un vote enregistré
qunad un user vote pour un sondage ca va creer un PollVote
avec l id du user, pour quel sondage (poll_id), et pour quelle option(poll_ption_id)
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends Model
{

    protected $fillable = ['user_id', 'poll_option_id'];

    /*
    du coup un vote est relié a un seul user, celui qui a fait le vote
    */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    un vote est lié du coup a une option precise
    */
    public function option(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'poll_option_id');
    }
}
