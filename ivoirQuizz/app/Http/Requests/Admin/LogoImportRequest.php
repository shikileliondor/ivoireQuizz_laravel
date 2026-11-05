<?php

namespace App\Http\Requests\Admin;

use App\Support\LogoImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class LogoImportRequest extends FormRequest
{
    /** PHP refuse par défaut plus de 20 fichiers par envoi (max_file_uploads). */
    public const MAX_FILES = 20;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return [
            'logos' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'logos.*' => LogoImage::rules(),
            'question_text' => ['required', 'string', 'max:1000'],
            'difficulty' => ['required', Rule::in(LevelRequest::DIFFICULTIES)],
            'image_source' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    /** Chaque erreur de fichier nomme le fichier concerné. */
    public function attributes(): array
    {
        $files = collect($this->file('logos', []))
            ->filter(fn ($file): bool => $file instanceof UploadedFile)
            ->mapWithKeys(fn (UploadedFile $file, int|string $key): array => ['logos.'.$key => '« '.$file->getClientOriginalName().' »'])
            ->all();

        return [
            ...$files,
            'logos' => 'logos',
            'question_text' => 'question',
            'difficulty' => 'difficulté',
            'image_source' => "source de l'image",
        ];
    }
}
