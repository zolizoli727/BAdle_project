<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class HistoryMemoryService
{
    public function resetHistoryMemory(): void
    {
        // daily reset
        if (Auth::check()) {
            return;
        }

        $today = now()->toDateString();
        if (session('guessHistoryDate') !== $today) {
            session()->forget('guessHistoryClassic');
            session()->forget('guessHistoryHard');
            session()->forget('guessHistoryImage');
            session()->put('guessHistoryDate', $today);
            session()->put('classicGameState', false);
            session()->put('hardGameState', false);
            session()->put('imageGameState', false);
            session()->put('messageBoxShowClassic', false);
            session()->put('messageBoxShowHard', false);
            session()->put('messageBoxShowImage', false);
        }
    }
}
