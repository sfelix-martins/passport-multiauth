<?php

namespace SMartins\PassportMultiauth\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Company extends Authenticatable
{
    protected $table = 'companies';

    use \Laravel\Passport\HasApiTokens;

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
