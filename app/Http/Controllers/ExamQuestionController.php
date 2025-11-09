<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Http\Request;

class ExamQuestionController extends Controller
{
    /**
     * List all questions for an exam.
     */
    public function index(Request $request, Exam $exam)
    {
        abort_unless($exam->course->teacher_id === $request->user()->id, 403);
        return $exam->questions;
    }

    /**
     * Add a question to an exam.
     */
    public function store(Request $request, Exam $exam)
    {
        abort_unless($exam->course->teacher_id === $request->user()->id, 403);

        $request->validate([
            'question'       => 'required|string',
            'correct_answer' => 'required|string|max:191',
        ]);

        return $exam->questions()->create($request->only(['question', 'correct_answer']));
    }

    /**
     * Update a question.
     */
    public function update(Request $request, Exam $exam, ExamQuestion $question)
    {
        abort_unless($exam->course->teacher_id === $request->user()->id, 403);

        $request->validate([
            'question'       => 'required|string',
            'correct_answer' => 'required|string|max:191',
        ]);

        $question->update($request->only(['question', 'correct_answer']));

        return $question;
    }

    /**
     * Delete a question.
     */
    public function destroy(Request $request, Exam $exam, ExamQuestion $question)
    {
        abort_unless($exam->course->teacher_id === $request->user()->id, 403);

        $question->delete();

        return response()->noContent();
    }
}