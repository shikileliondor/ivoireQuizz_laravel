<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthLinkController extends Controller
{
    public function verify(Request $request, int $id, string $hash): Response
    {
        abort_unless($request->hasValidRelativeSignature(), 403);

        $user = User::query()->findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Adresse email vérifiée.']);
        }

        return response()->view('site.auth-link', [
            'title' => 'Adresse vérifiée',
            'message' => 'Votre adresse email est confirmée. Vous pouvez revenir dans IvoirQuizz.',
        ]);
    }

    public function resetPassword(): Response
    {
        return response()->view('site.auth-link', [
            'title' => 'Ouvrir IvoirQuizz',
            'message' => 'Ouvrez ce lien depuis un appareil sur lequel IvoirQuizz est installé pour choisir un nouveau mot de passe.',
        ]);
    }

    public function android(): Response
    {
        $package = config('auth_links.android.package');
        $fingerprints = config('auth_links.android.sha256_cert_fingerprints', []);

        return response()->json($package && $fingerprints ? [[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => ['namespace' => 'android_app', 'package_name' => $package, 'sha256_cert_fingerprints' => $fingerprints],
        ]] : []);
    }
}
