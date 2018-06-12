<?php

namespace Ikerlin\Alipay;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AlipayExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('alipay', __NAMESPACE__ . '\Provider');
    }
}