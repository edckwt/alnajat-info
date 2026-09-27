<?php

namespace App\Jobs;

use App\Models\Publication;
use App\Services\PdfCache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** يجهّز ملف النشرة مسبقاً حتى لا ينتظر أول زائر توليده. */
class GeneratePublicationPdf implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 2;

    public function __construct(public readonly int $publicationId) {}

    public function uniqueId(): string
    {
        return (string) $this->publicationId;
    }

    public function handle(PdfCache $cache): void
    {
        $publication = Publication::published()->find($this->publicationId);

        if ($publication) {
            $cache->publication($publication);
        }
    }
}
