<?php

namespace SMartins\PassportMultiauth;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_access_token_providers';
    protected $primaryKey = 'oauth_access_token_id';
}
