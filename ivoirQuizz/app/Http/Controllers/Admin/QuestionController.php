<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\StoresImages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LevelRequest;
use App\Http\Requests\Admin\QuestionRequest;
use App\Models\Category;
use App\Models\Level;
use App\Models\Question;
use App\Services\Admin\QuestionAdminService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    use StoresImages;

    public function __construct(private readonly QuestionAdminService $questions) {}

    public function index(Request $request): View
    {
        $questions = Question::query()
            ->with(['level.city', 'category', 'answers' => fn ($query) => $query->where('is_correct', true)])
            ->withCount(['questionReports as pending_reports_count' => fn ($query) => $query->where('status', 'pending')])
            ->when($request->filled('q'), fn ($query) => $query->where('question_text', 'like', '%'.$request->string('q')->trim().'%'))
            ->when($request->filled('level'), fn ($query) => $query->where('level_id', $request->integer('level')))
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->filled('difficulty'), fn ($query) => $query->where('difficulty', $request->query('difficulty')))
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->query('source') === 'campaign', fn ($query) => $query->whereNotNull('source_key'))
            ->when($request->query('source') === 'manual', fn ($query) => $query->whereNull('source_key'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.questions.index', [
            'questions' => $questions,
            ...$this->formOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $level = $request->filled('level') ? Level::query()->find($request->integer('level')) : null;

        return view('admin.questions.form', [
            'question' => new Question([
                'level_id' => $level?->id,
                'difficulty' => $level?->difficulty ?? 'easy',
                'type' => 'text',
                'points' => 10,
                'xp_reward' => 5,
                'time_limit' => 20,
                'is_active' => true,
            ]),
            'answers' => collect(),
            ...$this->formOptions(),
        ]);
    }

    public function store(QuestionRequest $request): RedirectResponse
    {
        $attributes = $request->safe()->except(['image', 'answers', 'correct']);
        $attributes['image'] = $this->storeImage($request, 'image', 'questions', null);

        $question = $this->questions->save(new Question, $attributes, $request->validated('answers'), $request->validated('correct'));

        return redirect()
            ->route($request->boolean('create_another') ? 'admin.questions.create' : 'admin.questions.index', $request->boolean('create_another') ? ['level' => $question->level_id] : [])
            ->with('success', 'Question créée.');
    }

    public function edit(Question $question): View
    {
        return view('admin.questions.form', [
            'question' => $question,
            'answers' => $question->answers()->orderBy('order')->get(),
            ...$this->formOptions(),
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $attributes = $request->safe()->except(['image', 'answers', 'correct']);
        $attributes['image'] = $this->storeImage($request, 'image', 'questions', $question->image);

        $this->questions->save($question, $attributes, $request->validated('answers'), $request->validated('correct'));

        return redirect()->route('admin.questions.edit', $question)->with('success', 'Question mise à jour.');
    }

    public function toggle(Question $question): RedirectResponse
    {
        $question->update(['is_active' => ! $question->is_active]);

        return back()->with('success', $question->is_active ? 'Question activée.' : 'Question désactivée.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        return redirect()->route('admin.questions.index')->with('success', 'Question supprimée (archivée).');
    }

    /**
     * @return array{levels: Collection, categories: Collection, difficulties: list<string>, types: list<string>}
     */
    private function formOptions(): array
    {
        return [
            'levels' => Level::query()->with('city:id,name')->orderBy('city_id')->orderBy('order')->get(['id', 'title', 'city_id']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'difficulties' => LevelRequest::DIFFICULTIES,
            'types' => QuestionRequest::TYPES,
        ];
    }
}
