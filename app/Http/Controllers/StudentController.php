<?php

namespace App\Http\Controllers;

use App\Services\DailyStudentService;

class StudentController extends Controller
{
    public function __construct(
        protected DailyStudentService $dailyStudentService
    ) {}

    public function getDailyStudent(string $mode = 'classic')
    {
        // returns today's student for the specified mode
        $method = 'getDaily' . ucfirst($mode) . 'Student';

        if (!method_exists($this->dailyStudentService, $method)) {
            abort(404, "Invalid mode: {$mode}");
        }

        return $this->dailyStudentService->$method();
    }
}
