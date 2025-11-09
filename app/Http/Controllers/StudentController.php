<?php
namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\JoinRequest;
use App\Models\Quiz;
use App\Models\Exam;
use App\Models\QuizResult;
use App\Models\ExamResult;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function profile(Request $request)
    {
        return $request->user();
    }

    public function updateProfile(Request $request)
    {
        $request->validate(['name' => 'required|string|max:191']);
        $request->user()->update(['name' => $request->input('name')]);
        return $request->user();
    }

    /* ---------- join request ---------- */
    public function joinRequest(Request $request, ClassModel $class)
    {
        $user = $request->user();
        // one pending request max
        if (JoinRequest::where(['student_id' => $user->id, 'class_id' => $class->id])->exists()) {
            return response()->json(['message' => 'Request already sent'], 422);
        }
        $req = JoinRequest::create([
            'student_id' => $user->id,
            'class_id'   => $class->id,
            'status'     => 'pending',
        ]);
        return response()->json($req, 201);
    }

    public function myRequests(Request $request)
    {
        return JoinRequest::with('class')
            ->where('student_id', $request->user()->id)
            ->latest()
            ->get();
    }

    /* ---------- attempt quiz ---------- */
    public function attemptQuiz(Request $request, Quiz $quiz)
    {
        $user = $request->user();
        // must be enrolled in the class if private
        if (!$quiz->course->is_public && !$user->classes()->where('class_id', $quiz->course->class_id)->exists()) {
            abort(403, 'Not enrolled');
        }
        $answers = $request->input('answers');          // array [questionId => chosenOption]
        $score   = 0;
        foreach ($quiz->questions as $q) {
            if (($answers[$q->id] ?? null) === $q->correct_option) $score++;
        }
        $result = QuizResult::create([
            'quiz_id'    => $quiz->id,
            'student_id' => $user->id,
            'score'      => $score,
        ]);
        return response()->json(['score' => $score, 'total' => $quiz->questions->count()]);
    }

    /* ---------- attempt exam ---------- */
    public function attemptExam(Request $request, Exam $exam)
    {
        $user = $request->user();
        if (!$exam->course->is_public && !$user->classes()->where('class_id', $exam->course->class_id)->exists()) {
            abort(403, 'Not enrolled');
        }
        $answers = $request->input('answers');            // [questionId => textAnswer]
        $score   = 0;
        foreach ($exam->questions as $q) {
            if (trim(strtolower($answers[$q->id] ?? '')) === trim(strtolower($q->correct_answer))) $score++;
        }
        $result = ExamResult::create([
            'exam_id'    => $exam->id,
            'student_id' => $user->id,
            'score'      => $score,
        ]);
        return response()->json(['score' => $score, 'total' => $exam->questions->count()]);
    }

    /* ---------- grades ---------- */
    public function myGrades(Request $request)
    {
        $user = $request->user();
        return [
            'quizzes' => QuizResult::with('quiz.course')->where('student_id', $user->id)->latest()->get(),
            'exams'   => ExamResult::with('exam.course')->where('student_id', $user->id)->latest()->get(),
        ];
    }
}