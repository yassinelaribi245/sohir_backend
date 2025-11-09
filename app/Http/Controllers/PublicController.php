<?php
namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function courses(Request $request)
    {
        return Course::with('teacher','supports')
            ->where('is_public', true)
            ->latest()
            ->get();
    }

    public function show(Request $request, Course $course)
    {
        abort_unless($course->is_public, 404);
        return $course->load('teacher','supports','quizzes.questions','exams.questions');
    }
}