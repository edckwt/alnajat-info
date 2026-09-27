<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Publication;
use App\Services\PdfCache;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * pdf-show/{id} و pdf-publication/{id} و today-news.html.
 * يُعرض الملف في المتصفح، و ?download=1 لتنزيله.
 */
class PdfController extends Controller
{
    public function __construct(private readonly PdfCache $cache) {}

    public function news(Request $request, int $id): BinaryFileResponse
    {
        $news = News::published()->with(['newspaper', 'categories'])->findOrFail($id);

        return $this->send($request, $this->cache->news($news), 'news-'.$news->id.'.pdf');
    }

    public function publication(Request $request, int $id): BinaryFileResponse
    {
        return $this->sendPublication($request, Publication::published()->findOrFail($id));
    }

    /** آخر نشرة منشورة (كما في النظام القديم: ORDER BY id DESC). */
    public function today(Request $request): BinaryFileResponse
    {
        return $this->sendPublication($request, Publication::published()->latest('id')->firstOrFail());
    }

    private function sendPublication(Request $request, Publication $publication): BinaryFileResponse
    {
        $version = (int) $publication->pdf_version ?: (int) config('alnajat.pdf_latest_version');
        $date = ($publication->publication_date ?? $publication->created_at ?? now())->format('j-m-Y');

        return $this->send($request, $this->cache->publication($publication), "Alnajat-News-{$version}-{$date}.pdf");
    }

    private function send(Request $request, string $path, string $name): BinaryFileResponse
    {
        $headers = [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'public, max-age=300',
            'X-Robots-Tag' => 'noindex',
        ];

        return $request->boolean('download')
            ? response()->download($path, $name, $headers)
            : response()->file($path, $headers + ['Content-Disposition' => 'inline; filename="'.$name.'"']);
    }
}
