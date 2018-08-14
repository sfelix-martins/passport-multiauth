<?php

namespace SMartins\PassportMultiauth\Tests\Fixtures\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\HasApiTokens;

class User extends Authenticatable
{
    protected $table = 'users';

    use HasApiTokens;

    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public static function createUser()
    {
        \DB::table('users')->insert([
            'name' => 'Samuel',
            'email' => 'sam.martins.dev@gmail.com',
            'password' => \Hash::make('456'),
        ]);
    }
}
