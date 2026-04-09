<?php

namespace Jinomial\LaravelSsl\Support;

use Illuminate\Support\Collection;

/**
 * @extends Collection<int, Certificate>
 *
 * @psalm-api
 */
final class CertificateCollection extends Collection
{
    /**
     * Check if the entire certificate chain is valid.
     */
    public function isValid(): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        return $this->every(fn (Certificate $cert) => $cert->isValid());
    }

    /**
     * Check if any certificate in the chain is expired.
     */
    public function hasExpired(): bool
    {
        return $this->contains(fn (Certificate $cert) => $cert->isExpired());
    }
}
