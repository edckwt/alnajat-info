<?php

namespace App\Http\Middleware;

use App\Support\Theme;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يطبّق واجهة الموقع المعتمدة في الإعدادات.
 * من يملك settings.general يستطيع معاينة واجهة أخرى قبل اعتمادها: ?theme=modern (تبقى في جلسته)،
 * و ?theme=off لإنهاء المعاينة. الزوار يرون دائماً الواجهة المعتمدة.
 */
class ApplySiteTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        $theme = Theme::active();

        if ($request->user()?->can('settings.general') && $request->hasSession()) {
            $session = $request->session();

            if ($request->has('theme')) {
                $wanted = (string) $request->query('theme');
                Theme::exists($wanted) && $wanted !== $theme
                    ? $session->put(Theme::PREVIEW_KEY, $wanted)
                    : $session->forget(Theme::PREVIEW_KEY);
            }

            $preview = $session->get(Theme::PREVIEW_KEY);
            if (Theme::exists($preview) && $preview !== $theme) {
                $theme = $preview;
            } else {
                $session->forget(Theme::PREVIEW_KEY);
            }
        }

        Theme::apply($theme);

        return $next($request);
    }
}
