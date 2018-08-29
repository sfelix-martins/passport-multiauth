<?php

namespace SMartins\PassportMultiauth\Tests\Fixtures\Models;

use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    protected $table = 'admins';

    use HasMultiAuthApiTokens;

    public function getAuthIdentifierName()
    {
        return 'id';
    }
}
