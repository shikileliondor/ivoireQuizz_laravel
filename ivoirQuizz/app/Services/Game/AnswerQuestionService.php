<?php

namespace App\Services\Game;

use App\Enums\GameConstants;
use App\Exceptions\Game\InvalidAnswerException;
use App\Exceptions\Game\InvalidGameSessionException;
use App\Exceptions\Game\QuestionAlreadyAnsweredException;
use App\Models\Answer;
use App\Models\GameSession;
use App\Models\GameSessionAnswer;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnswerQuestionService
{
    public function submitAnswer(GameSession $session, Question $question, ?Answer $answer, int $responseTime): GameSessionAnswer
    {
        return DB::transaction(function () use ($session, $question, $answer, $responseTime): GameSessionAnswer {
            $session = GameSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($session->status !== GameConstants::STATUS_STARTED || $session->started_at?->lt(now()->subHours(GameConstants::MAX_SESSION_HOURS))) { throw new InvalidGameSessionException('Cannot answer in this session.'); }
            if ($question->level_id !== $session->level_id) { Log::warning('Question outside session level', ['session_id' => $session->id, 'question_id' => $question->id]); throw new InvalidAnswerException('Question does not belong to this session.'); }
            if ($responseTime < 0 || $responseTime > $question->time_limit) { throw new InvalidAnswerException('Invalid response time.'); }
            if ($answer && $answer->question_id !== $question->id) { throw new InvalidAnswerException('Answer does not belong to this question.'); }
            if (GameSessionAnswer::query()->where('game_session_id', $session->id)->where('question_id', $question->id)->exists()) { Log::warning('Duplicate question answer attempt', ['session_id' => $session->id, 'question_id' => $question->id]); throw new QuestionAlreadyAnsweredException('Question already answered.'); }
            if ($session->gameSessionAnswers()->count() >= $session->total_questions) { throw new InvalidGameSessionException('Too many answers for this session.'); }

            $isCorrect = (bool) ($answer?->is_correct ?? false);
            $points = $isCorrect ? $this->points($question, $responseTime) : 0;
            $xp = $isCorrect ? (int) $question->xp_reward : 0;
            $row = GameSessionAnswer::query()->create(['game_session_id' => $session->id, 'question_id' => $question->id, 'answer_id' => $answer?->id, 'is_correct' => $isCorrect, 'response_time' => $responseTime, 'points_earned' => $points, 'xp_earned' => $xp]);
            $session->increment('score', $points);
            $session->increment('points_earned', $points);
            $session->increment('xp_earned', $xp);
            return $row;
        });
    }

    private function points(Question $question, int $responseTime): int
    {
        $difficulty = ['easy' => 1.0, 'medium' => 1.2, 'hard' => 1.5, 'expert' => 2.0][$question->difficulty] ?? 1.0;
        $timeRatio = max(0, ($question->time_limit - $responseTime) / max(1, $question->time_limit));
        return (int) round($question->points * $difficulty * (1 + $timeRatio));
    }
}
