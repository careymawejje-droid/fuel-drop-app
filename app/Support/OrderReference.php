<?php

namespace App\Support;

use App\Models\Order;
use RuntimeException;

final class OrderReference
{
    private const PREFIX = 'FD';
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    private const RANDOM_PART_LENGTH = 6;
    private const MAX_ATTEMPTS = 100;

    /**
     * Generate an unused human-readable order reference. The database unique
     * index remains the final guard against simultaneous insert collisions.
     * The callback is exposed for deterministic collision tests.
     */
    public static function generate(?callable $candidateFactory = null): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $candidate = $candidateFactory ? $candidateFactory() : self::randomCandidate();

            if (preg_match('/\AFD[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}\z/', $candidate) !== 1) {
                throw new RuntimeException('Order reference candidates must use the standard eight-character format.');
            }

            if (! Order::query()->where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to generate a unique order reference after multiple attempts.');
    }

    private static function randomCandidate(): string
    {
        $lastIndex = strlen(self::ALPHABET) - 1;
        $suffix = '';

        for ($i = 0; $i < self::RANDOM_PART_LENGTH; $i++) {
            $suffix .= self::ALPHABET[random_int(0, $lastIndex)];
        }

        return self::PREFIX.$suffix;
    }
}
