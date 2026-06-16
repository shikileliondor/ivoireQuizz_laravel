<?php

namespace App\Services;

class QuizScoreCalculator
{
    public const BASE_POINTS_PER_CORRECT = 100;
    public const MAX_TIME_BONUS = 50;
    public const STREAK_STEP_BONUS = 20;
    public const PERFECT_BONUS = 250;
    public const MAX_RESPONSE_TIME_SECONDS = 20;

    /**
     * @param  array<int, array{is_correct: bool, response_time_seconds: int}>  $answers
     * @return array{
     *     total_score: int,
     *     score: int,
     *     bonus_score: int,
     *     base_score: int,
     *     time_bonus_score: int,
     *     streak_bonus_score: int,
     *     perfect_bonus_score: int,
     *     correct_answers: int,
     *     max_streak: int,
     *     all_correct: bool,
     *     per_answer: array<int, array{base_points: int, time_bonus: int, streak_bonus: int, points_earned: int}>
     * }
     */
    public function calculate(array $answers): array
    {
        $baseScore = 0;
        $timeBonusScore = 0;
        $streakBonusScore = 0;
        $perfectBonusScore = 0;
        $correctAnswers = 0;
        $currentStreak = 0;
        $maxStreak = 0;
        $perAnswer = [];

        foreach ($answers as $answer) {
            $isCorrect = (bool) $answer['is_correct'];
            $responseTime = max(0, (int) $answer['response_time_seconds']);

            if (! $isCorrect) {
                $currentStreak = 0;
                $perAnswer[] = [
                    'base_points' => 0,
                    'time_bonus' => 0,
                    'streak_bonus' => 0,
                    'points_earned' => 0,
                ];

                continue;
            }

            $correctAnswers++;
            $currentStreak++;
            $maxStreak = max($maxStreak, $currentStreak);

            $basePoints = self::BASE_POINTS_PER_CORRECT;
            $timeBonus = $this->calculateTimeBonus($responseTime);
            $streakBonus = ($currentStreak - 1) * self::STREAK_STEP_BONUS;

            $baseScore += $basePoints;
            $timeBonusScore += $timeBonus;
            $streakBonusScore += $streakBonus;

            $answerTotal = max(0, $basePoints + $timeBonus + $streakBonus);

            $perAnswer[] = [
                'base_points' => $basePoints,
                'time_bonus' => $timeBonus,
                'streak_bonus' => $streakBonus,
                'points_earned' => $answerTotal,
            ];
        }

        $allCorrect = count($answers) > 0 && $correctAnswers === count($answers);

        if ($allCorrect) {
            $perfectBonusScore = self::PERFECT_BONUS;
        }

        $bonusScore = max(0, $timeBonusScore + $streakBonusScore + $perfectBonusScore);
        $score = max(0, $baseScore);
        $totalScore = max(0, $score + $bonusScore);

        return [
            'total_score' => $totalScore,
            'score' => $score,
            'bonus_score' => $bonusScore,
            'base_score' => $score,
            'time_bonus_score' => $timeBonusScore,
            'streak_bonus_score' => $streakBonusScore,
            'perfect_bonus_score' => $perfectBonusScore,
            'correct_answers' => $correctAnswers,
            'max_streak' => $maxStreak,
            'all_correct' => $allCorrect,
            'per_answer' => $perAnswer,
        ];
    }

    private function calculateTimeBonus(int $responseTimeSeconds): int
    {
        $capped = min(self::MAX_RESPONSE_TIME_SECONDS, max(0, $responseTimeSeconds));

        return max(0, self::MAX_TIME_BONUS - ($capped * 2));
    }
}
