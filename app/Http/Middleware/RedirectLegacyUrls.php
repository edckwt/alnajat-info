<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * الروابط القديمة بصيغة index.php?action=... تُحوَّل (301) إلى الروابط النظيفة
 * التي كانت .htaccess تنتجها، حتى تبقى روابط واتساب وجوجل القديمة صالحة.
 */
class RedirectLegacyUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        $action = $request->query('action');
        $pdf = $request->query('read') === 'pdf';
        $id = (int) $request->query('id');
        $publicationId = (int) $request->query('publication_id');

        $target = match (true) {
            $action === 'show' && $id > 0 => url(($pdf ? 'pdf-show/' : 'show/').$id),
            $action === 'category' && $id > 0 => url('category/'.$id),
            $action === 'search' && filled($request->query('s')) => url('search/'.rawurlencode((string) $request->query('s'))),
            $action === 'publication' && $publicationId > 0 => url(($pdf ? 'pdf-publication/' : 'publication/').$publicationId),
            $action === 'publications' => url('archive.html'),
            $action === 'news' => url('/'),
            // رابط «تحميل النشرة» في أرشيف الموقع القديم
            $action === null && $pdf && $publicationId > 0 => url('pdf-publication/'.$publicationId),
            $action === null && $pdf => url('today-news.html'),
            default => null,
        };

        return $target ? redirect()->to($target, 301) : $next($request);
    }
}
