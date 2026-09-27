<?php

use App\Console\Commands\CheckLinks;
use App\Models\Category;
use App\Models\News;
use App\Models\Publication;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Category::forceCreate(['id' => 1, 'name' => 'محليات', 'is_active' => true]);
    foreach (range(1, 5) as $i) {
        News::forceCreate(['title' => "خبر {$i}", 'type' => 1, 'is_active' => true, 'published_date' => '2026-09-25']);
    }
    Publication::forceCreate(['title' => 'نشرة', 'publication_date' => '2026-09-25', 'is_active' => true]);
});

it('passes when new links answer 200 and old ones 301 to a working page', function () {
    Http::fake([
        '*/index.php*' => Http::response('', 301, ['Location' => 'https://site.test/show/1']),
        '*' => Http::response('ok', 200),
    ]);

    $this->artisan('alnajat:check-links', ['base' => 'https://site.test', '--sample' => 20, '--source' => 'new'])
        ->assertSuccessful();

    Http::assertSent(fn ($request) => $request->header('User-Agent')[0] === CheckLinks::USER_AGENT);
});

it('fails when a link is broken', function () {
    Http::fake([
        '*/show/*' => Http::response('', 404),
        '*/index.php*' => Http::response('', 301, ['Location' => 'https://site.test/show/1']),
        '*' => Http::response('ok', 200),
    ]);

    $this->artisan('alnajat:check-links', ['base' => 'https://site.test', '--sample' => 20, '--source' => 'new'])
        ->assertFailed();
});

it('does not count the link checker as a visit', function () {
    $news = News::first();

    $this->withHeader('User-Agent', CheckLinks::USER_AGENT)->get('show/'.$news->id)->assertOk();

    expect($news->fresh()->views)->toBe(0);
});
