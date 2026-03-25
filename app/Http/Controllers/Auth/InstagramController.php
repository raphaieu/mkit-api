<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Creator\CreateCreatorAction;
use App\Http\Controllers\Controller;
use App\Services\InstagramSyncService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Throwable;

class InstagramController extends Controller
{
    public function redirect(): RedirectResponse
    {
        // Scopes são gerenciados pelo provider (instagram_business_basic por padrão).
        /** @var AbstractProvider $provider */
        $provider = Socialite::driver('instagram');

        return $provider->stateless()->redirect();
    }

    public function callback(CreateCreatorAction $createCreator, InstagramSyncService $syncService): RedirectResponse
    {
        try {
            /** @var AbstractProvider $provider */
            $provider = Socialite::driver('instagram');
            $socialiteUser = $provider->stateless()->user();

            ['token' => $longLivedToken, 'expires_at' => $expiresAt] =
                $this->exchangeForLongLivedToken($socialiteUser->token);

            $user = $createCreator->execute($socialiteUser, $longLivedToken, $expiresAt);

            // Fetch full profile data (bio, followers, posts) immediately so the
            // frontend never sees an empty profile on first login.
            try {
                $syncService->syncForUser($user);
            } catch (Throwable $e) {
                // Non-fatal: user is authenticated, sync can be retried later.
                Log::warning('Initial Instagram sync failed after OAuth', [
                    'user_id'   => $user->id,
                    'exception' => $e::class,
                    'error'     => $e->getMessage(),
                ]);
            }

            $token = $user->createToken('instagram-spa')->plainTextToken;

            return redirect(config('app.frontend_url').'/app?token='.$token);
        } catch (RequestException $e) {
            $apiError = $e->response->json('error.message', $e->getMessage());

            Log::error('Instagram token exchange failed', [
                'status' => $e->response->status(),
                'error' => $apiError,
            ]);

            return $this->failRedirect('token_exchange', $apiError);
        } catch (Throwable $e) {
            Log::error('Instagram OAuth callback failed', [
                'exception' => $e::class,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->failRedirect('oauth_failed', $e->getMessage());
        }
    }

    /**
     * Exchange the short-lived token (1h) for a long-lived token (60 days).
     *
     * @see https://developers.facebook.com/docs/instagram-basic-display-api/guides/long-lived-access-tokens
     *
     * @return array{token: string, expires_at: Carbon}
     */
    private function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        $response = Http::get('https://graph.instagram.com/access_token', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => config('services.instagram.client_secret'),
            'access_token' => $shortLivedToken,
        ])->throw()->json();

        return [
            'token' => $response['access_token'],
            'expires_at' => Carbon::now()->addSeconds((int) $response['expires_in']),
        ];
    }

    private function failRedirect(string $reason, string $detail = ''): RedirectResponse
    {
        $url = config('app.frontend_url').'/auth/error?reason='.$reason;

        // Expose the error message in non-production environments so it shows
        // up in the browser / frontend console during development.
        if (! app()->isProduction() && $detail !== '') {
            $url .= '&detail='.urlencode($detail);
        }

        return redirect($url);
    }
}
