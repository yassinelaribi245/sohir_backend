<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;   // ← REQUIRED
use Illuminate\Database\Eloquent\Relations\BelongsTo;   // optional but nice for type-hints
use Illuminate\Database\Eloquent\Relations\HasMany;
class ExamQuestion extends Model
{
    protected $fillable = ['exam_id', 'question', 'correct_answer'];

    public function exam() {
        return $this->belongsTo(Exam::class);
    }
}