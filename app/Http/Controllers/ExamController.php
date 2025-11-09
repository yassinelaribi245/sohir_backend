<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Course;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    /**
     * List all exams that belong to the authenticated teacher.
     */
    public function index(Request $request)
    {
        return Exam::with('course')
            ->whereHas('course', fn($q) => $q->where('teacher_id', $request->user()->id))
            ->latest()
            ->get();
    }

    /**
     * Create a new exam for a teacher-owned course.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'     => 'required|string|max:191',
            'course_id' => 'required|exists:courses,id',
        ]);

        // ensure the course belongs to the teacher
        abort_unless(
            Course::find($request->input('course_id'))->teacher_id === $request->user()->id,
            403
        );

        return Exam::create($request->only(['title', 'course_id']));
    }

    /**
     * Show one exam (with questions).
     */
    public function show(Request $request, Exam $exam)
    {
        abort_unless($exam->course->teacher_id === $request->user()->id, 403);
        return $exam->load('questions');
    }

    /**
     * Update exam title.
     */
    public function update(Request $request, Exam $exam)
    {
        abort_unless($exam->course->teacher_id === $request->user()->id, 403);

        $request->validate(['title' => 'required|string|max:191']);

        $exam->update($request->only('title'));

        return $exam;
    }

    /**
     * Delete an exam.
     */
    public function destroy(Request $request, Exam $exam)
    {
        abort_unless($exam->course->teacher_id === $request->user()->id, 403);

        $exam->delete();

        return response()->noContent();
    }
}