<?php

namespace SMartins\PassportMultiauth\Tests\Fixtures\Models;

use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Company extends Authenticatable
{
    protected $table = 'companies';

    use HasMultiAuthApiTokens;

    public function getAuthIdentifierName()
    {
        return 'id';
    }
}
