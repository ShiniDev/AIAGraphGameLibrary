<?php

namespace GraphLib\Traits;

trait SerializableIdentity
{
    /**
     * Generates a v4 compliant UUID.
     * @return string
     */
    private function generateSID(): string
    {
        // This implementation is a valid way to generate a UUID v4.
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
