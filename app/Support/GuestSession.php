<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestSession
{
    public static function id(Request $request): string
    {
        $token = self::headerToken($request);
        if ($token !== '') {
            return 'app:'.$token;
        }

        if ($request->hasSession()) {
            return (string) $request->session()->getId();
        }

        $generated = (string) $request->attributes->get('guest_token');
        if ($generated === '') {
            $generated = (string) Str::uuid();
            $request->attributes->set('guest_token', $generated);
        }

        return 'app:'.$generated;
    }

    public static function publicToken(Request $request): ?string
    {
        $header = self::headerToken($request);
        if ($header !== '') {
            return $header;
        }

        $generated = $request->attributes->get('guest_token');

        return is_string($generated) && $generated !== '' ? $generated : null;
    }

    private static function headerToken(Request $request): string
    {
        $raw = trim((string) $request->header('X-Cart-Token', ''));
        if ($raw === '') {
            return '';
        }

        return substr((string) preg_replace('/[^A-Za-z0-9\-_]/', '', $raw), 0, 64);
    }
}
