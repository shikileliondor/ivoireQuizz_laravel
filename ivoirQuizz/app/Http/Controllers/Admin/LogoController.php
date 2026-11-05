<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LevelRequest;
use App\Http\Requests\Admin\LogoImportRequest;
use App\Models\Category;
use App\Models\Question;
use App\Services\Admin\LogoImportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vue dédiée aux questions de la catégorie Logos : grille de vignettes et import en lot.
 * La création et l'édition unitaires passent par le formulaire des questions.
 */
class LogoController extends Controller
{
    public function __construct(private readonly LogoImportService $imports) {}

    public function index(Request $request): View
    {
        $categoryId = Category::logosId();
        $missingSource = fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('image_source')->orWhere('image_source', ''));

        $logos = Question::query()
            ->where('category_id', $categoryId ?? 0)
            ->with(['answers' => fn ($query) => $query->where('is_correct', true)])
            ->when($request->filled('q'), fn (Builder $query) => $query->whereHas('answers', fn (Builder $answers) => $answers
                ->where('is_correct', true)
                ->where('answer_text', 'like', '%'.$request->string('q')->trim().'%')))
            ->when($request->query('status') === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->when($request->query('source') === 'missing', $missingSource)
            ->latest('id')
            ->paginate(48)
            ->withQueryString();

        return view('admin.logos.index', [
            'logos' => $logos,
            'categoryExists' => $categoryId !== null,
            'missingSourceCount' => $categoryId === null ? 0 : $missingSource(Question::query()->where('category_id', $categoryId))->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.logos.import', [
            'categoryExists' => Category::logosId() !== null,
            'difficulties' => LevelRequest::DIFFICULTIES,
        ]);
    }

    public function store(LogoImportRequest $request): RedirectResponse
    {
        $categoryId = Category::logosId();
        if ($categoryId === null) {
            return back()->with('error', 'La catégorie Logos (slug « logos ») n’existe pas encore : créez-la d’abord.');
        }

        $result = $this->imports->import($categoryId, $request->file('logos'), [
            'question_text' => $request->validated('question_text'),
            'difficulty' => $request->validated('difficulty'),
            'image_source' => $request->validated('image_source'),
            'is_active' => $request->boolean('is_active'),
        ]);

        $skipped = $result['skipped'] === [] ? '' : ' Déjà présents, ignorés : '.implode(', ', $result['skipped']).'.';

        if ($result['created'] === []) {
            return back()->with('error', 'Aucun logo importé.'.$skipped);
        }

        $review = $request->boolean('is_active') ? '' : ' Ils restent inactifs : vérifiez les mauvaises réponses proposées, puis activez-les.';

        return redirect()
            ->route('admin.logos.index')
            ->with('success', count($result['created']).' logo(s) importé(s).'.$review.$skipped);
    }
}
