<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Support\LogoImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuestionRequest extends FormRequest
{
    public const TYPES = ['text', 'image', 'audio'];

    public const MAX_ANSWERS = 6;

    private ?int $logosId = null;

    private bool $logosIdLoaded = false;

    /** La question est-elle rangée dans la catégorie Logos ? */
    public function isLogo(): bool
    {
        $logosId = $this->logosId();

        return $logosId !== null && (int) $this->input('category_id') === $logosId;
    }

    private function logosId(): ?int
    {
        if (! $this->logosIdLoaded) {
            $this->logosId = Category::logosId();
            $this->logosIdLoaded = true;
        }

        return $this->logosId;
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Les lignes de réponse laissées vides sont ignorées ; les clés sont conservées
     * pour que le bouton radio « correct » désigne toujours la bonne ligne.
     */
    protected function prepareForValidation(): void
    {
        $answers = collect($this->input('answers', []))
            ->filter(fn ($answer): bool => is_array($answer) && trim((string) ($answer['text'] ?? '')) !== '')
            ->all();

        $this->merge([
            'answers' => $answers,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'question_text' => ['required', 'string', 'max:1000'],
            'type' => ['required', Rule::in(self::TYPES)],
            'difficulty' => ['required', Rule::in(LevelRequest::DIFFICULTIES)],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'points' => ['required', 'integer', 'min:0', 'max:1000'],
            'xp_reward' => ['required', 'integer', 'min:0', 'max:1000'],
            'time_limit' => ['required', 'integer', 'min:5', 'max:300'],
            'is_active' => ['boolean'],
            'image' => $this->isLogo()
                ? ['nullable', ...LogoImage::rules()]
                : ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'image_source' => ['nullable', 'string', 'max:255'],
            'answers' => ['required', 'array', 'min:2', 'max:'.self::MAX_ANSWERS],
            'answers.*.id' => ['nullable', 'integer'],
            'answers.*.text' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'correct' => ['required'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! array_key_exists((string) $this->input('correct'), $this->input('answers', []))) {
                    $validator->errors()->add('correct', 'Choisissez la bonne réponse parmi les réponses remplies.');
                }

                $keepsImage = $this->route('question')?->image && ! $this->boolean('remove_image');
                if ($this->input('type') === 'image' && ! $this->hasFile('image') && ! $keepsImage) {
                    $validator->errors()->add('image', 'Une question de type image a besoin d’une image.');
                }

                // Un logo ne se joue que dans sa catégorie, la seule où l'app affiche la mention légale.
                if ($this->isLogo()) {
                    if ($this->filled('level_id')) {
                        $validator->errors()->add('level_id', 'Un logo ne se rattache à aucun niveau : il se joue seulement dans le lancement rapide, où l’app affiche la mention légale.');
                    }
                    if ($this->input('type') !== 'image') {
                        $validator->errors()->add('type', 'Une question Logos doit être de type image.');
                    }
                } elseif ($this->logosId() !== null && $this->input('type') === 'image'
                    && preg_match('/\blogos?\b/iu', (string) $this->input('question_text'))) {
                    $validator->errors()->add('category_id', 'Cette question montre un logo : rangez-la dans la catégorie Logos, la seule où l’app affiche la mention légale.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'level_id' => 'niveau',
            'category_id' => 'catégorie',
            'question_text' => 'question',
            'difficulty' => 'difficulté',
            'time_limit' => 'temps limite',
            'image_source' => "source de l'image",
            'answers' => 'réponses',
            'answers.*.text' => 'réponse',
            'correct' => 'bonne réponse',
        ];
    }
}
