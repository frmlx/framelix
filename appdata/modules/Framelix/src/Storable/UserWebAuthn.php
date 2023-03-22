<?php

namespace Framelix\Framelix\Storable;

use Framelix\Framelix\Url;
use Framelix\Framelix\View;

/**
 * UserWebAuthn
 * @property User $user
 * @property string $deviceName
 * @property mixed|null $challenge
 * @property mixed|null $authData
 */
class UserWebAuthn extends StorableExtended
{
    /**
     * Get edit url
     * @return Url|null
     */
    public function getDetailsUrl(): ?Url
    {
        return View::getUrl(View\Backend\UserProfile\Index::class)->setParameter(
            'editWebAuthn',
            $this
        )->setHash('tabs:fido2');
    }

}