<?php

namespace SMartins\PassportMultiauth\Auth;

use Illuminate\Auth\AuthManager as BaseAuthManager;

class AuthManager extends BaseAuthManager
{
    /**
     * Clear guards cache to resolve it again.
     */
    public function clearGuardsCache()
    {
        $this->guards = [];
    }
}
