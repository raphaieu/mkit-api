<?php

namespace App\Socialite;

use SocialiteProviders\Instagram\Provider as BaseProvider;

/**
 * Extends the community Instagram provider to use the current Meta OAuth URL.
 *
 * The vendor package still points to https://api.instagram.com/oauth/authorize
 * (the old Basic Display API path). The new Instagram Login API for Business
 * accounts uses https://www.instagram.com/oauth/authorize.
 *
 * The token exchange endpoint (https://api.instagram.com/oauth/access_token)
 * and user data endpoint (https://graph.instagram.com/me) remain unchanged.
 */
class InstagramProvider extends BaseProvider
{
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            'https://www.instagram.com/oauth/authorize',
            $state,
        );
    }
}
