<?php

declare(strict_types=1);

namespace Grav\Plugin;

use Grav\Common\Plugin;

/**
 * Derivux by Gravister.
 *
 * Initial 0.1.0 bootstrap. Theme derivation functionality is intentionally
 * not implemented yet; this class only establishes a valid Grav plugin entry
 * point for subsequent development.
 */
class DerivuxPlugin extends Plugin
{
    public static function getSubscribedEvents(): array
    {
        return [];
    }
}
