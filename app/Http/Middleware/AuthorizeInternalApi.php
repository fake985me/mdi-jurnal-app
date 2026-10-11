<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthorizeInternalApi
{
    public function handle(Request $request, Closure $next)
    {
        if (in_array($request->path(), ['api/user', 'api/logout'], true)) {
            return $next($request);
        }
        $role = $request->user()?->role;
        abort_unless(in_array($role, ['superadmin', 'admin', 'sales', 'staff', 'viewer'], true), 403);
        if ($role === 'viewer') {
            abort_unless($request->isMethod('GET') || $request->isMethod('HEAD'), 403);
        }
        $controller = class_basename($request->route()->getControllerClass());
        if (in_array($controller, [
            'SolutionController', 'ProjectController', 'SiteSettingController',
            'ContactInfoController', 'CarouselController', 'PublicProductController',
            'PageController', 'PageSectionController', 'PageTemplateController',
        ], true)) {
            abort_unless(in_array($role, ['superadmin', 'admin'], true), 403);
        }
        return $next($request);
    }
}
