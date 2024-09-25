<?php

namespace SocialiteProviders\Devops;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DevopsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('devops', Provider::class);
    }
}
