<?php

namespace SMartins\PassportMultiauth;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $provider The provider related with access token
 */
class Provider extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'oauth_access_token_providers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'oauth_access_token_id';
}
