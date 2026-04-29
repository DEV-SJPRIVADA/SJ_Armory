<?php

namespace App\Services;

use Illuminate\Support\Str;

final class UserTemporaryPasswordGenerator
{
    public function generate(int $length = 14): string
    {
        return Str::password($length, symbols: true, numbers: true, letters: true, spaces: false);
    }
}
