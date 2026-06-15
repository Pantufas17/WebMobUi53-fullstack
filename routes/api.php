<?php
/*
fichier qui va me declarer toutes les routes de l api
*/

use App\Http\Controllers\Api\v1\ApiPostController;
use App\Http\Controllers\Api\v1\ApiFooController;
use App\Http\Controllers\Api\v1\ApiPollController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::apiResource('v1/posts', ApiPostController::class)
    ->middlewareFor(['index', 'show'], ['auth:sanctum', 'abilities:posts:read'])
    ->middlewareFor(['store'], ['auth:sanctum', 'abilities:posts:create'])
    ->middlewareFor(['update'], ['auth:sanctum', 'abilities:posts:update'])
    ->middlewareFor(['destroy'], ['auth:sanctum', 'abilities:posts:delete']);


/*
route publique pour afficher un sondage via son token
on le met pas apres dans le truc auth parce que tout le monde doit 
pouvoir acceder au truc de vote meme si on a pas de compte
*/
Route::get('/v1/polls/{token}', [ApiPollController::class, 'show']);


/*
du coup apres ici toutes les autres routes qui necessitent que le user 
soit connecté sinon une erreur 401 et rederiger la personne vers la page login
*/
Route::middleware('auth:sanctum')->group(function () {
    //les deux de bases qu il y avait
    Route::get('/v1/foo', [ApiFooController::class, 'show']);
    Route::post('/v1/foo', [ApiFooController::class, 'store']);
    //liste de mes sondages avec l index
    Route::get('/v1/polls', [ApiPollController::class, 'index']);
    //route pour la creation dun sondage
    Route::post('/v1/polls', [ApiPollController::class, 'store']);
    //route pour supprimer un sondage
    Route::delete('/v1/polls/{poll}', [ApiPollController::class, 'destroy']);
    //route pour mettre a jour un sondage
    Route::put('/v1/polls/{poll}', [ApiPollController::class, 'update']);
    //route pour voter a un sondage, on passe le token et pas l id
    //pour du coup bien pouvoir afficher le sondage et en voter
    Route::post('/v1/polls/{token}/vote', [ApiPollController::class, 'vote']);
});
