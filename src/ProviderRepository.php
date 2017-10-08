<?php

namespace SMartins\PassportMultiauth;

use Carbon\Carbon;

class ProviderRepository
{
    public function findForToken($tokenId)
    {
        return Provider::where('oauth_access_token_id', $tokenId)->first();
    }

    public function create($token, $provider)
    {
        $provider = (new Provider)->forceFill([
            'oauth_access_token_id' => $token,
            'provider' => $provider,
            'created_at' => new Carbon(),
            'updated_at' => new Carbon(),
        ]);

        $provider->save();

        return $provider;
    }
}
