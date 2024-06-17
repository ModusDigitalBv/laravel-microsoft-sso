<?php

namespace ModusDigital\LaravelMicrosoftSso\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OneLogin\Saml2\Auth as SamlAuth;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\ValidationError;

class MicrosoftSsoController extends Controller
{
    private SamlAuth $auth;

    /**
     * Constructor to initialize the SamlAuth object
     *
     * @throws Error
     */
    public function __construct()
    {
        $this->auth = new SamlAuth(config('sso'));
    }

    /**
     * Creating a new user if the user does not exist in the database or logging in the user if the user exists
     *
     * @return RedirectResponse
     * @throws ValidationError
     * @throws Error
     */
    public function acs(): RedirectResponse
    {
        $this->auth->processResponse();

        if ($this->auth->isAuthenticated()) {
            $user = User::where(
                'email',
                $this->auth->getAttributes()['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress'][0]
            )->first();

            if ($user) {
                auth()->login($user);

                return redirect()->to('/');
            }

            $newUser = User::create([
                'email' => $this->auth->getAttributes()['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress'][0],
                'firstname' => $this->auth->getAttributes()['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname'][0],
                'lastname' => $this->auth->getAttributes()['http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname'][0],
                'display_name' => $this->auth->getAttributes()['http://schemas.microsoft.com/identity/claims/displayname'][0],
                'password' => Hash::make(Str::random()),
                'provider' => 'microsoft_sso',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Auth::login($newUser);
            return redirect()->to('/');
        }

        return redirect()
            ->route('login')
            ->withErrors('Failed to authenticate user');
    }

    /**
     * Returns the metadata of the SP
     *
     * @return Response
     * @throws Error
     */
    public function metadata(): Response
    {
        return response($this->auth->getSettings()->getSPMetadata(), 200, [
            'Content-Type' => 'text/xml',
        ]);
    }

    /**
     * Handles the login request from the IDP
     *
     * @return RedirectResponse
     */
    public function login(): RedirectResponse
    {
        try {
            if (! $this->auth->isAuthenticated()) {
                $this->auth->login();
            }

            return redirect()->intended();
        } catch (Error $e) {
            return redirect()
                ->route('login')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Logs the user out of the SP
     *
     * @return RedirectResponse|null
     */
    public function logout(): ?RedirectResponse
    {
        try {
            $this->auth->logout();
        } catch (Error $e) {
            return redirect()
                ->route('login')
                ->with('error', $e->getMessage());
        }

        return null;
    }

    /**
     * Handles the SLS request from the IDP
     *
     * @return RedirectResponse
     * @throws Error
     */
    public function sls(): RedirectResponse
    {
        $this->auth->processSLO();

        if (auth()->check()) {
            session()->flush();
            session()->invalidate();
            session()->regenerate();

            Auth::logout();
        }

        return redirect()
            ->intended(route('login'));
    }
}
