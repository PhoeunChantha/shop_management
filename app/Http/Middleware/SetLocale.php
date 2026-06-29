<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported UI locales.
     *
     * @var array<int, string>
     */
    public const SUPPORTED = ['en', 'km'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale'));

        if (in_array($locale, self::SUPPORTED, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
