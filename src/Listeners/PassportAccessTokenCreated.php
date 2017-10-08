<?php

namespace SMartins\PassportMultiauth\Listeners;

use DB;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Laravel\Passport\Events\AccessTokenCreated;
use SMartins\PassportMultiauth\ProviderRepository;

class PassportAccessTokenCreated
{
    private $providers;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ProviderRepository $providers)
    {
        $this->providers = $providers;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(AccessTokenCreated $event)
    {
        $provider = config('auth.guards.api.provider');

        $this->providers->create($event->tokenId, $provider);
    }
}
