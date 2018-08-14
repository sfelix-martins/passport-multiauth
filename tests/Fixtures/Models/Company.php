<?php

namespace SMartins\PassportMultiauth\Tests\Fixtures\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\HasApiTokens;

class Company extends Authenticatable
{
    protected $table = 'companies';

    use HasApiTokens;

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public static function createCompany()
    {
        \DB::table('companies')->insert([
            'name' => 'Samuel',
            'email' => 'sam.martins.dev@gmail.com',
            'password' => \Hash::make('456'),
        ]);
    }
}
