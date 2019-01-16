<?php

namespace Infinety\TemplySettings\Http\Middleware;

use Infinety\TemplySettings\TemplySettings;

class Authorize
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     */
    public function handle($request, $next)
    {
        return resolve(TemplySettings::class)->authorize($request) ? $next($request) : abort(403);
    }
}
