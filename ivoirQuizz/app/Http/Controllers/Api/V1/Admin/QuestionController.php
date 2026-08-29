<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\QuestionImportRequest;
use App\Http\Requests\Api\V1\Admin\QuestionRequest;
use App\Http\Resources\Api\V1\Admin\AdminQuestionResource;
use App\Models\Question;
use App\Services\Admin\AdminContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class QuestionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AdminContentService $content,
    ) {}

    public function index(Request $request)
    {
        $questions = Question::query()
            ->with(['answers' => fn ($q) => $q->orderBy('order'), 'category', 'level.chapter'])
            ->withCount([
                'pendingReports',
                'gameSessionAnswers as times_answered',
                'gameSessionAnswers as times_correct' => fn ($q) => $q->where('is_correct', true),
            ])
            ->when($request->filled('level_id'), fn ($q) => $q->where('level_id', $request->integer('level_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('difficulty'), fn ($q) => $q->where('difficulty', $request->string('difficulty')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('chapter_id'), fn ($q) => $q->whereHas('level', fn ($sub) => $sub->where('chapter_id', $request->integer('chapter_id'))))
            ->when($request->filled('region_id'), fn ($q) => $q->whereHas('level.chapter', fn ($sub) => $sub->where('region_id', $request->integer('region_id'))))
            ->when($request->filled('search'), fn ($q) => $q->where('question_text', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->boolean('reported_only'), fn ($q) => $q->whereHas('pendingReports'))
            ->orderByDesc('id')
            ->paginate(min((int) $request->integer('per_page', 25), 100));

        return AdminQuestionResource::collection($questions)->response();
    }

    public function store(QuestionRequest $request)
    {
        try {
            $data = $request->validated();
            $answers = $data['answers'];
            unset($data['answers']);

            $question = $this->content->saveQuestionWithAnswers(new Question, $data, $answers);

            return $this->successResponse(new AdminQuestionResource($question), 'Question créée.', 201);
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin question store failed');
        }
    }

    public function show(Question $question)
    {
        $question->load(['answers' => fn ($q) => $q->orderBy('order'), 'category', 'level.chapter'])
            ->loadCount([
                'pendingReports',
                'gameSessionAnswers as times_answered',
                'gameSessionAnswers as times_correct' => fn ($q) => $q->where('is_correct', true),
            ]);

        return $this->successResponse(new AdminQuestionResource($question));
    }

    public function update(QuestionRequest $request, Question $question)
    {
        try {
            $data = $request->validated();
            $answers = $data['answers'] ?? null;
            unset($data['answers']);

            $question = $this->content->saveQuestionWithAnswers($question, $data, $answers);

            return $this->successResponse(new AdminQuestionResource($question), 'Question mise à jour.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin question update failed');
        }
    }

    public function destroy(Question $question)
    {
        try {
            $question->delete();

            return $this->successResponse(null, 'Question archivée.');
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin question destroy failed');
        }
    }

    /**
     * Bulk entry from a spreadsheet. The whole batch is one transaction: a
     * partial import would leave the editor unsure which rows landed.
     */
    public function import(QuestionImportRequest $request)
    {
        try {
            $payload = $request->validated();
            $levelId = (int) $payload['level_id'];

            $created = DB::transaction(function () use ($payload, $levelId): int {
                $count = 0;

                foreach ($payload['questions'] as $row) {
                    $answers = $row['answers'];
                    unset($row['answers']);

                    $correct = collect($answers)->filter(fn ($a) => filter_var($a['is_correct'], FILTER_VALIDATE_BOOLEAN))->count();

                    if ($correct !== 1) {
                        throw new \RuntimeException(
                            'Ligne « '.mb_substr($row['question_text'], 0, 40).' » : il faut exactement une bonne réponse.'
                        );
                    }

                    $row['level_id'] = $levelId;
                    $this->content->saveQuestionWithAnswers(new Question, $row, $answers);
                    $count++;
                }

                return $count;
            });

            return $this->successResponse(['created' => $created], "$created question(s) importée(s).", 201);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), [], 422);
        } catch (Throwable $e) {
            return $this->businessError($e, 'admin question import failed');
        }
    }
}
