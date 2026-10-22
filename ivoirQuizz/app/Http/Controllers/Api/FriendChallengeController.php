<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FriendChallengeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $challenges = DB::table('friend_challenges as c')
            ->join('users as challenger', 'challenger.id', '=', 'c.challenger_id')
            ->join('users as opponent', 'opponent.id', '=', 'c.opponent_id')
            ->where(fn ($q) => $q->where('c.challenger_id', $userId)->orWhere('c.opponent_id', $userId))
            ->orderByDesc('c.created_at')
            ->limit(50)
            ->get(['c.code', 'c.status', 'c.challenger_id', 'c.opponent_id', 'c.challenger_score', 'c.opponent_score', 'challenger.name as challenger_name', 'opponent.name as opponent_name', 'c.created_at']);

        return response()->json(['success' => true, 'data' => ['challenges' => $challenges]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'opponent_id' => ['required', 'integer', 'exists:users,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ])->validate();
        $challenger = $request->user();
        $opponentId = (int) $data['opponent_id'];
        if ($opponentId === (int) $challenger->id || ! $this->areFriends((int) $challenger->id, $opponentId)) {
            return response()->json(['success' => false, 'message' => 'Un défi est possible uniquement avec un ami accepté.'], 422);
        }

        $questions = Question::query()->where('is_active', true)
            ->when(! empty($data['category_id']), fn ($q) => $q->where('category_id', $data['category_id']))
            ->with(['answers:id,question_id,answer_text,is_correct,order'])
            ->inRandomOrder()->limit(10)->get();
        if ($questions->count() !== 10) {
            return response()->json(['success' => false, 'message' => 'Il faut 10 questions actives pour créer ce défi.'], 422);
        }

        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        } while (DB::table('friend_challenges')->where('code', $code)->exists());

        DB::table('friend_challenges')->insert([
            'code' => $code, 'challenger_id' => $challenger->id, 'opponent_id' => $opponentId,
            'category_id' => $data['category_id'] ?? null,
            'question_ids' => json_encode($questions->modelKeys()), 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'data' => ['code' => $code, 'questions' => $this->formatQuestions($questions), 'status' => 'pending']], 201);
    }

    public function show(Request $request, string $code): JsonResponse
    {
        $challenge = DB::table('friend_challenges')->where('code', strtoupper($code))->first();
        if (! $challenge) {
            return response()->json(['success' => false, 'message' => 'Défi introuvable.'], 404);
        }
        if (! in_array((int) $request->user()->id, [(int) $challenge->challenger_id, (int) $challenge->opponent_id], true)) {
            return response()->json(['success' => false, 'message' => 'Ce défi ne vous est pas destiné.'], 403);
        }
        $questions = Question::query()->whereIn('id', json_decode($challenge->question_ids, true) ?: [])
            ->with(['answers:id,question_id,answer_text,is_correct,order'])->get()
            ->sortBy(fn ($q) => array_search($q->id, json_decode($challenge->question_ids, true), true))->values();

        return response()->json(['success' => true, 'data' => ['code' => $challenge->code, 'status' => $challenge->status, 'questions' => $this->formatQuestions($questions), 'challenger_score' => $challenge->challenger_score, 'opponent_score' => $challenge->opponent_score]]);
    }

    public function submit(Request $request, string $code): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'answers' => ['required', 'array', 'size:10'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.answer_id' => ['nullable', 'integer'],
            'answers.*.response_time' => ['required', 'integer', 'min:0', 'max:30'],
        ])->validate();

        return DB::transaction(function () use ($request, $code, $data): JsonResponse {
            $challenge = DB::table('friend_challenges')->where('code', strtoupper($code))->lockForUpdate()->first();
            if (! $challenge) {
                return response()->json(['success' => false, 'message' => 'Défi introuvable.'], 404);
            }
            $userId = (int) $request->user()->id;
            $isChallenger = $userId === (int) $challenge->challenger_id;
            if (! $isChallenger && $userId !== (int) $challenge->opponent_id) {
                return response()->json(['success' => false, 'message' => 'Ce défi ne vous est pas destiné.'], 403);
            }
            $scoreColumn = $isChallenger ? 'challenger_score' : 'opponent_score';
            if ($challenge->{$scoreColumn} !== null) {
                return response()->json(['success' => false, 'message' => 'Votre score a déjà été enregistré.'], 409);
            }
            $questionIds = json_decode($challenge->question_ids, true) ?: [];
            if (array_values(array_unique(array_map(fn ($a) => (int) $a['question_id'], $data['answers']))) !== array_values(array_map('intval', $questionIds))) {
                return response()->json(['success' => false, 'message' => 'Les réponses ne correspondent pas au quiz du défi.'], 422);
            }
            $correct = 0;
            $score = 0;
            $questionPoints = Question::query()->whereIn('id', $questionIds)->pluck('points', 'id');
            foreach ($data['answers'] as $answer) {
                $option = ! empty($answer['answer_id']) ? DB::table('answers')->where('id', $answer['answer_id'])->where('question_id', $answer['question_id'])->first() : null;
                if ($option && $option->is_correct) {
                    $correct++;
                    $score += (int) ($questionPoints[$answer['question_id']] ?? 0);
                }
            }
            $updates = [$scoreColumn => $score, $isChallenger ? 'challenger_correct' : 'opponent_correct' => $correct, $isChallenger ? 'challenger_answers' : 'opponent_answers' => json_encode($data['answers']), 'updated_at' => now()];
            $otherScore = $isChallenger ? $challenge->opponent_score : $challenge->challenger_score;
            if ($otherScore !== null) {
                $updates['status'] = 'completed';
            } else {
                $updates['status'] = 'in_progress';
            }
            DB::table('friend_challenges')->where('id', $challenge->id)->update($updates);
            $updated = DB::table('friend_challenges')->where('id', $challenge->id)->first();

            return response()->json(['success' => true, 'data' => ['code' => $updated->code, 'status' => $updated->status, 'your_score' => $score, 'your_correct' => $correct, 'challenger_score' => $updated->challenger_score, 'opponent_score' => $updated->opponent_score, 'winner_id' => $updated->status === 'completed' ? ((int) $updated->challenger_score === (int) $updated->opponent_score ? null : ((int) $updated->challenger_score > (int) $updated->opponent_score ? (int) $updated->challenger_id : (int) $updated->opponent_id)) : null]]);
        });
    }

    private function areFriends(int $a, int $b): bool
    {
        return Friendship::query()->accepted()->where(fn ($q) => $q->where('requester_id', $a)->where('receiver_id', $b)->orWhere('requester_id', $b)->where('receiver_id', $a))->exists();
    }

    private function formatQuestions($questions): array
    {
        return $questions->map(fn ($q) => [
            'id' => $q->id, 'question_text' => $q->question_text ?? $q->text ?? $q->title ?? '',
            'points' => (int) $q->points,
            'explanation' => $q->explanation, 'difficulty' => $q->difficulty ?? 1,
            'options' => $q->answers->shuffle()->values()->map(fn ($a) => ['id' => $a->id, 'option_text' => $a->answer_text, 'is_correct' => (bool) $a->is_correct])->all(),
        ])->all();
    }
}
