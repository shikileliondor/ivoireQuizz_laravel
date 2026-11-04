<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Services\Admin\PushNotificationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SendPushRequest extends FormRequest
{
    private ?User $targetUser = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'audience' => ['required', Rule::in(PushNotificationService::AUDIENCES)],
            'days' => ['nullable', 'required_if:audience,active,inactive', 'integer', 'min:1', 'max:365'],
            'user' => ['nullable', 'required_if:audience,user', 'string', 'max:191'],
            'platform' => ['nullable', Rule::in(PushNotificationService::PLATFORMS)],
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
            'when' => ['nullable', Rule::in(['now', 'later'])],
            'send_at' => ['nullable', 'required_if:when,later', 'date', 'after:now', 'before:+60 days'],
        ];
    }

    /**
     * Le joueur ciblé se désigne par email, pseudo ou code ami.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('audience') !== 'user' || $validator->errors()->has('user')) {
                    return;
                }

                $search = trim((string) $this->input('user'));
                $this->targetUser = User::query()
                    ->where('email', $search)
                    ->orWhere('username', $search)
                    ->orWhere('friend_code', strtoupper($search))
                    ->first();

                if ($this->targetUser === null) {
                    $validator->errors()->add('user', 'Aucun joueur trouvé avec cet email, pseudo ou code ami.');
                }
            },
        ];
    }

    public function targetUser(): ?User
    {
        return $this->targetUser;
    }

    public function attributes(): array
    {
        return [
            'audience' => 'destinataires',
            'days' => 'nombre de jours',
            'user' => 'joueur',
            'platform' => 'plateforme',
            'title' => 'titre',
            'body' => 'message',
            'send_at' => 'date d’envoi',
        ];
    }
}
