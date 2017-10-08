<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOauthAccessTokenProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oauth_access_token_providers', function (Blueprint $table) {
            $table->string('oauth_access_token_id', 100)->primary();
            $table->string('provider');
            $table->timestamps();
            $table->foreign('oauth_access_token_id')
                ->references('id')
                ->on('oauth_access_tokens')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('oauth_access_token_providers');
    }
}
