<?php

namespace AppBundle\Service;


class ShortCodeGenerator
{
    // generate token with $length number of characters
    public function generateShortCode()
    {
        $length = 3;
        $shortCode = bin2hex(random_bytes($length));
        return $shortCode;
    }
}