<?php

use App\Http\Controllers\FormController;
use App\Http\Controllers\DebugController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Http\Controllers\QuizController;
use App\Services\HardModeService;
use App\Services\UserGuessService;
use App\Services\GuestGuessService;
use App\Services\DailyStudentService;
use App\Services\StatisticService;
use Illuminate\Support\Facades\Artisan;

Route::get('/robots.txt', function () {
    $baseUrl = rtrim(config('app.url', url('/')), '/');

    $lines = [
        'User-agent: *',
        'Disallow:',
        'Sitemap: ' . $baseUrl . '/sitemap.xml',
    ];

    return response(implode(PHP_EOL, $lines), 200)->header('Content-Type', 'text/plain');
});

Route::get('/sitemap.xml', function () {
    $baseUrl = rtrim(config('app.url'), '/');
    $lastMod = now()->toAtomString();

    $pages = [
        '/',
        '/classic',
        '/hard',
        '/image',
        '/about',
        '/login',
        '/register'
    ];

    $urls = collect($pages)->map(function ($path) use ($baseUrl, $lastMod) {
        return "
        <url>
            <loc>{$baseUrl}{$path}</loc>
            <lastmod>{$lastMod}</lastmod>
            <priority>0.8</priority>
        </url>";
    })->implode("\n");

    return response("
    <?xml version=\"1.0\" encoding=\"UTF-8\"?>
    <urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">
        {$urls}
    </urlset>", 200)->header('Content-Type', 'application/xml');
});


Route::get('/', function () {
    $modes = ['classic', 'hard', 'image'];
    $props = [];

    app(\App\Services\HistoryMemoryService::class)->resetHistoryMemory();
    $user = Auth::user();
    $guessService = app(UserGuessService::class);
    $guestGuessService = app(GuestGuessService::class);
    $dailyService = app(DailyStudentService::class);
    $guestToken = request()->attributes->get('guest_token');

    foreach ($modes as $mode) {
        $key = ucfirst($mode);
        $dailyHistory = $dailyService->getDailyHistoryRecord($mode);
        $props["dailyStudent{$key}"] = $dailyHistory->student;
        $latestStudent = null;
        $matchesPayload = [];
        $heightStatus = null;

        if ($user) {
            $historyEntries = $guessService->historyPayload($user, $dailyHistory);
            $hasCompleted = $guessService->userHasCompleted($user, $dailyHistory);
            $props["guessHistory{$key}"] = $historyEntries;
            $props["attempts{$key}"] = count($historyEntries);
            $props["{$mode}GameState"] = $hasCompleted;
            $props["messageBoxShow{$key}"] = $hasCompleted;
            $props["guessCorrect{$key}"] = $guessService->lastGuessResult($user, $dailyHistory);

            $latestGuess = $guessService->latestGuessForHistory($user, $dailyHistory);
            $latestStudent = $latestGuess?->student;
            $matchesPayload = $latestGuess?->matches ?? [];
            $heightStatus = $latestGuess?->height_status;
        } elseif ($guestToken) {
            $historyEntries = $guestGuessService->historyPayload($guestToken, $dailyHistory);
            $hasCompleted = $guestGuessService->guestHasCompleted($guestToken, $dailyHistory);
            $props["guessHistory{$key}"] = $historyEntries;
            $props["attempts{$key}"] = count($historyEntries);
            $props["{$mode}GameState"] = $hasCompleted;
            $props["messageBoxShow{$key}"] = $hasCompleted;
            $props["guessCorrect{$key}"] = $guestGuessService->lastGuessResult($guestToken, $dailyHistory);

            $latestGuess = $guestGuessService->latestGuessForHistory($guestToken, $dailyHistory);
            $latestStudent = $latestGuess?->student;
            $matchesPayload = $latestGuess?->matches ?? [];
            $heightStatus = $latestGuess?->height_status;
        } else {
            $props["guessCorrect{$key}"] = null;
            $props["guessHistory{$key}"] = [];
            $props["{$mode}GameState"] = false;
            $props["messageBoxShow{$key}"] = false;
            $props["attempts{$key}"] = 0;
        }

        $props["guessedStudentData{$key}"] = $latestStudent;
        $props["matches{$key}"] = $matchesPayload;
        $props["heightStatus{$key}"] = $heightStatus;
    }

    $secondsUntilReset = now()->diffInSeconds(now()->endOfDay());
    $hardModeClues = app(HardModeService::class)->getDailyHardModeClues();

    $statistics = app(StatisticService::class)->dbQueries($user);

    return Inertia::render('Home', array_merge([
        'hardModeClues' => $hardModeClues,
        'secondsUntilReset' => $secondsUntilReset,
        'statistics' => $statistics,
    ], $props));
})->name('home');

Route::get('/sidebar-stats', function (Request $request) {
    $mode = strtolower($request->query('mode', 'classic'));
    $availableModes = ['classic', 'hard', 'image'];

    if (!in_array($mode, $availableModes, true)) {
        return response()->json([
            'message' => 'Invalid mode supplied.',
        ], 422);
    }

    $user = Auth::user();
    $guestToken = $request->attributes->get('guest_token');
    $statisticService = app(StatisticService::class);
    $dailyStudentService = app(DailyStudentService::class);
    $guessService = app(UserGuessService::class);
    $guestGuessService = app(GuestGuessService::class);

    $statistics = $statisticService->dbQueries($user);
    $todayGuesses = (int)($statistics['todayGuesses'] ?? 0);
    $todayCorrectByMode = (array)($statistics['todayCorrectGuessesByMode'] ?? []);
    $todayCorrect = (int)($todayCorrectByMode[$mode] ?? 0);

    $attempts = 0;
    $dailyHistory = $dailyStudentService->getDailyHistoryRecord($mode);

    if ($user) {
        $attempts = $guessService->attemptCount($user, $dailyHistory);
    } elseif ($guestToken) {
        $attempts = $guestGuessService->attemptCount($guestToken, $dailyHistory);
    }

    return response()->json([
        'todayGuesses' => $todayGuesses,
        'todayCorrectGuesses' => $todayCorrect,
        'currentAttempts' => $attempts,
    ]);
})->name('sidebar-stats');

Route::post('/guess-student', [FormController::class, 'guessStudent'])->name('guess-student');
Route::post('/handler', [FormController::class, 'guessedHandler'])->name('handle-guess');
Route::get('/students/search', [FormController::class, 'search']);

Route::middleware('auth')->group(function () {
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'verified', 'admin', 'throttle:10,1'])->group(function () {
    Route::post('/clear-history/{mode}', [DebugController::class, 'clearHistory'])->name('clear-history');

    Route::post('/toggle-game-state/{mode}', function ($mode) {
        $current = session("{$mode}GameState", false);
        $newState = !$current;

        session(["{$mode}GameState" => $newState]);

        return response()->json([
            "{$mode}GameState" => $newState
        ]);
    })->name('toggle-game-state');

    Route::post('/get-hard-mode-clues', [QuizController::class, 'getHardModeClues'])->name('get-hard-mode-clues');
});

