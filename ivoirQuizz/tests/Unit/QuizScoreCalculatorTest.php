<?php

namespace Tests\Unit;

use App\Services\QuizScoreCalculator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuizScoreCalculatorTest extends TestCase
{
    #[Test]
    public function it_calculates_all_score_components_and_perfect_bonus(): void
    {
        $calculator = new QuizScoreCalculator();

        $result = $calculator->calculate([
            ['is_correct' => true, 'response_time_seconds' => 2],
            ['is_correct' => true, 'response_time_seconds' => 6],
            ['is_correct' => true, 'response_time_seconds' => 11],
        ]);

        $this->assertSame(300, $result['base_score']);
        $this->assertSame(88, $result['time_bonus_score']);
        $this->assertSame(60, $result['streak_bonus_score']);
        $this->assertSame(250, $result['perfect_bonus_score']);
        $this->assertSame(398, $result['bonus_score']);
        $this->assertSame(698, $result['total_score']);
        $this->assertSame(3, $result['correct_answers']);
        $this->assertSame(3, $result['max_streak']);
        $this->assertTrue($result['all_correct']);
        $this->assertSame(3, count($result['per_answer']));
    }

    #[Test]
    public function it_resets_streak_bonus_after_wrong_answer_and_never_returns_negative_points(): void
    {
        $calculator = new QuizScoreCalculator();

        $result = $calculator->calculate([
            ['is_correct' => true, 'response_time_seconds' => 20],
            ['is_correct' => false, 'response_time_seconds' => 3],
            ['is_correct' => true, 'response_time_seconds' => 18],
        ]);

        $this->assertSame(200, $result['base_score']);
        $this->assertSame(14, $result['time_bonus_score']);
        $this->assertSame(0, $result['streak_bonus_score']);
        $this->assertSame(0, $result['perfect_bonus_score']);
        $this->assertSame(214, $result['total_score']);
        $this->assertSame(1, $result['max_streak']);

        $this->assertSame(0, $result['per_answer'][1]['points_earned']);
        $this->assertGreaterThanOrEqual(0, $result['total_score']);
    }
}
