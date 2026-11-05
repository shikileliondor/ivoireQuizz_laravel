<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\AccountDeletionRequest;
use App\Services\User\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Suppression de compte depuis le web, sans l'application : exigée par Google Play
 * pour toute application qui permet de créer un compte.
 */
class AccountDeletionController extends Controller
{
    public function create(): View
    {
        return view('site.account-deletion');
    }

    public function store(AccountDeletionRequest $request, AccountService $accounts): RedirectResponse
    {
        $accounts->delete($request->authenticatedUser());

        return redirect()->route('site.account-deletion')->with('deleted', true);
    }
}
