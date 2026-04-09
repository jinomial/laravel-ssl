<?php

namespace Jinomial\LaravelSsl\Support;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 *
 * @psalm-api
 */
final class Certificate implements Arrayable, Jsonable, JsonSerializable
{
    /**
     * Create a new Certificate instance.
     */
    public function __construct(
        public readonly array $raw,
        public readonly ?array $verification = null,
        public readonly ?string $host = null,
        public readonly ?string $port = null
    ) {
    }

    /**
     * Get the issuer common name.
     */
    public function getIssuerCommonName(): ?string
    {
        return $this->raw['issuer']['CN'] ?? null;
    }

    /**
     * Get the Subject Alternative Names.
     */
    public function getSubjectAlternativeNames(): array
    {
        $extensions = $this->raw['extensions'] ?? [];
        $sans = $extensions['subjectAltName'] ?? '';

        if (empty($sans)) {
            return [];
        }

        return array_map('trim', explode(',', $sans));
    }

    /**
     * Get the common name of the certificate.
     */
    public function getCommonName(): ?string
    {
        return $this->raw['subject']['CN'] ?? null;
    }

    /**
     * Check if the certificate is currently valid.
     */
    public function isValid(): bool
    {
        return ! $this->isExpired() && $this->isVerificationSuccessful();
    }

    /**
     * Check if the certificate is expired.
     */
    public function isExpired(): bool
    {
        $validTo = $this->getValidTo();

        return $validTo ? $validTo->isPast() : true;
    }

    /**
     * Check if the verification was successful.
     */
    public function isVerificationSuccessful(): bool
    {
        if (is_null($this->verification)) {
            return true;
        }

        return (int) ($this->verification['code'] ?? -1) === 0;
    }

    /**
     * Get the "valid from" date.
     */
    public function getValidFrom(): ?Carbon
    {
        if (! isset($this->raw['validFrom_time_t'])) {
            return null;
        }

        return Carbon::createFromTimestamp($this->raw['validFrom_time_t']);
    }

    /**
     * Get the "valid to" date.
     */
    public function getValidTo(): ?Carbon
    {
        if (! isset($this->raw['validTo_time_t'])) {
            return null;
        }

        return Carbon::createFromTimestamp($this->raw['validTo_time_t']);
    }

    /**
     * Convert the certificate to an array.
     */
    #[\Override]
    public function toArray(): array
    {
        return array_merge($this->raw, [
            'host' => $this->host,
            'port' => $this->port,
            'verification' => $this->verification,
            'is_valid' => $this->isValid(),
            'is_expired' => $this->isExpired(),
            'common_name' => $this->getCommonName(),
            'valid_from' => $this->getValidFrom()?->toIso8601String(),
            'valid_to' => $this->getValidTo()?->toIso8601String(),
        ]);
    }

    /**
     * Convert the certificate to JSON.
     */
    #[\Override]
    public function toJson($options = 0): string
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if ($json === false) {
            throw new \RuntimeException('Failed to encode certificate to JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
