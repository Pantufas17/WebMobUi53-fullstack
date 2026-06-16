<?php
/*
toutes les routes web pages HTML contraireemnt au api.php qui gere les routes json
Ici les routes vont du coup retourner une vue blade.
*/

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MyProfileController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PollDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TokenController;
use App\Models\Post;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $posts = Post::orderBy('created_at', 'desc')->with('user')->with('likes')->limit(3)->get();

    return view('home', ['posts' => $posts]);
});

Route::get('/about', function () {
    return view('about');
});




Route::get('/@{username}', [ProfileController::class, 'show'])->where('username', '[A-Za-z0-9-_]+');

Route::resource('posts', PostController::class)->only(['index', 'show']);


//routes d'authentification fournis deja (login, register, logout)
Route::controller(AuthController::class)->group(function () {
    Route::get('/auth/register', 'showRegister');
    Route::post('/auth/register', 'register');
    Route::get('/auth/login', 'showLogin')->name('login');
    Route::post('/auth/login', 'login');
});


//routes protegees qui sont necessaires qu on soit conectés
Route::middleware('auth')->group(function () {
    Route::get('/polls/dashboard', PollDashboardController::class)->name('polls.dashboard');
    // Route::get('/polls/dashboard-integrated', fn() => view('polls.dashboard-integrated'))
    //     ->name('polls.dashboard-integrated');
    Route::resource('posts', PostController::class)->except(['index', 'show']);
    Route::singleton('my-profile', MyProfileController::class)->destroyable();
    Route::match(['put', 'patch'], '/likes/{post}', [LikeController::class, 'update']);
    Route::resource('tokens', TokenController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});


//route publique pour la page de vote pour les sondages
//accessible sans etre connecte tjrs avec ce token
//le regex ->where() assure que le token ne contien que des lettres et chiffres
//evite du coup les conflits avec dautres routes comme polls/dashboard
Route::get('/polls/{token}', function (string $token) {
    return view('polls.viewer', ['token' => $token]);
})->where('token', '[A-Za-z0-9]+');
