<?php

namespace App\Services\Game;

use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class QuestionReportService
{
    public function report(User $user, Question $question, string $reason, ?string $message): QuestionReport
    {
        $recent = QuestionReport::query()
            ->where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($recent) {
            Log::warning('Duplicate question report attempt', ['user_id' => $user->id, 'question_id' => $question->id]);
            throw new InvalidArgumentException('Cette question a déjà été signalée récemment.');
        }

        return QuestionReport::query()->create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'reason' => $reason,
            'message' => $message,
            'status' => 'pending',
        ]);
    }
}
