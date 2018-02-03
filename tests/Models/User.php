<?php

namespace SMartins\PassportMultiauth\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    use \Laravel\Passport\HasApiTokens;

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
