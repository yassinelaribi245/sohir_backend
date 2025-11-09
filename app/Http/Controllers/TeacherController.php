<?php
namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\JoinRequest;
use App\Models\Quiz;
use App\Models\Exam;
use App\Models\User;
use App\Models\QuizResult;
use App\Models\ExamResult;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function joinRequests(Request $request)
    {
        $ids = ClassModel::where('teacher_id', $request->user()->id)->pluck('id');
        return JoinRequest::with(['student', 'class'])
            ->whereIn('class_id', $ids)
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    public function acceptRequest(Request $request, JoinRequest $joinRequest)
    {
        abort_unless($joinRequest->class->teacher_id === $request->user()->id, 403);
        $joinRequest->update(['status' => 'accepted']);
        $joinRequest->class->students()->attach($joinRequest->student_id);
        return response()->json(['message' => 'Student accepted']);
    }

    public function rejectRequest(Request $request, JoinRequest $joinRequest)
    {
        abort_unless($joinRequest->class->teacher_id === $request->user()->id, 403);
        $joinRequest->update(['status' => 'rejected']);
        return response()->json(['message' => 'Student rejected']);
    }

    public function myClasses(Request $request)
    {
        return ClassModel::withCount('students')
            ->where('teacher_id', $request->user()->id)
            ->latest()
            ->get();
    }

    public function classStudents(Request $request, ClassModel $class)
    {
        abort_unless($class->teacher_id === $request->user()->id, 403);
        return $class->students()->get();
    }

    public function removeStudent(Request $request, ClassModel $class, User $student)
    {
        abort_unless($class->teacher_id === $request->user()->id, 403);
        $class->students()->detach($student->id);
        JoinRequest::where(['class_id' => $class->id, 'student_id' => $student->id])->delete();
        return response()->json(['message' => 'Student removed']);
    }

    public function quizResults(Request $request, Quiz $quiz)
    {
        abort_unless($quiz->course->teacher_id === $request->user()->id, 403);
        return QuizResult::with('student')
            ->where('quiz_id', $quiz->id)
            ->latest()
            ->get();
    }

    public function examResults(Request $request, Exam $exam)
    {
        abort_unless($exam->course->teacher_id === $request->user()->id, 403);
        return ExamResult::with('student')
            ->where('exam_id', $exam->id)
            ->latest()
            ->get();
    }
}