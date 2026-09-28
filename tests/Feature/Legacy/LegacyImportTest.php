<?php

use App\Legacy\LegacyImporter;
use App\Legacy\LegacyTransform;
use App\Legacy\LegacyVerifier;
use App\Models\HomeBox;
use App\Models\News;
use App\Models\Publication;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * قاعدة قديمة مصغّرة في الذاكرة بنفس أسماء الجداول والأعمدة،
 * وفيها الحالات الصعبة التي وُجدت في البيانات الحقيقية.
 */
function legacyDatabase(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec(<<<'SQL'
        CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, username TEXT, password TEXT, email TEXT, active INT, user_group INT, date TEXT);
        CREATE TABLE category (id INTEGER PRIMARY KEY, title TEXT, description TEXT, active INT, user_id INT, date TEXT, update_user_id INT, update_date TEXT);
        CREATE TABLE newspaper (id INTEGER PRIMARY KEY, name TEXT, logo TEXT, url TEXT, country TEXT, description TEXT, text TEXT, active INT, user_id INT, date TEXT, update_user_id INT, update_date TEXT, type INT);
        CREATE TABLE banners (id INTEGER PRIMARY KEY, title TEXT, url TEXT, image TEXT, description TEXT, text TEXT, active INT, visit INT, user_id INT, date TEXT, update_user_id INT, update_date TEXT);
        CREATE TABLE news (id INTEGER PRIMARY KEY, title TEXT, url TEXT, image TEXT, description TEXT, text TEXT, newspaper_id INT, newspaper_number INT, published_date TEXT, active INT, hide_in_pdf INT, visit INT, visit_pdf INT, hide_title INT, hide_description INT, hide_more INT, user_id INT, date TEXT, update_user_id INT, update_date TEXT, tweet_url TEXT, sound_url TEXT, video_url TEXT, original_image INT, type INT, orders INT);
        CREATE TABLE news_meta (id INTEGER PRIMARY KEY, meta_key TEXT, meta_value TEXT, news_id INT);
        CREATE TABLE publications (id INTEGER PRIMARY KEY, title TEXT, description TEXT, url TEXT, image TEXT, text TEXT, active INT, user_id INT, date TEXT, update_user_id INT, update_date TEXT, publication_date TEXT, cover INT, other_file TEXT);
        CREATE TABLE setting (id INTEGER PRIMARY KEY, meta_key TEXT, meta_value TEXT);
        CREATE TABLE upload (id INTEGER PRIMARY KEY, title TEXT, url TEXT, user_id INT, date TEXT, size TEXT, type TEXT, mime TEXT);

        INSERT INTO users VALUES (7, 'علاء', 'alaagaber', '5ebe2294ecd0e0f08eab7690d2a6ee69', 'alaa@example.com', 1, 0, '1575275838');
        INSERT INTO users VALUES (6, 'موقوف', 'mtaha', '5ebe2294ecd0e0f08eab7690d2a6ee69', 'm@example.com', 0, 0, '1566284302');

        INSERT INTO category VALUES (1, 'محليات', '', 1, 7, '1557913294', 0, '');
        INSERT INTO category VALUES (3, 'دولي', '', 1, 1, '1557913294', 0, '');
        INSERT INTO newspaper VALUES (4, 'القبس', 'http://www.alnajat.info/upload/newspaper_1.png', '', 'kw', '', '', 1, 7, '1557913294', 0, '', 1);
        INSERT INTO banners VALUES (2, 'تبرع', 'https://example.org', 'https://alnajat.info/upload/banners_1.jpg', '', '', 1, 12, 7, '1565091582', 0, '');

        -- خبر 10: كاتبه (1) محذوف، صورته برابط مطلق، ونصه فيه رابط http للموقع.
        INSERT INTO news VALUES (10, 'خبر أول', '', 'http://alnajat.info/upload/news_10.png', 'وصف', '<p><img src="http://www.alnajat.info/upload/x.jpg"></p>', 4, 0, '2019-07-28', 1, 0, 332, 5, 0, 0, 0, 1, '1557913294', 7, '1565010563', '', '', '', 0, 1, 2);
        -- خبر 11: بلا صحيفة وبلا تاريخ نشر.
        INSERT INTO news VALUES (11, 'خبر ثان', '', '', '', 'نص', 0, 0, '', 0, 1, 0, 0, 1, 0, 0, 7, '1557913340', 0, '', '', '', '', 0, 4, 0);

        INSERT INTO news_meta VALUES (1, 'category_id', '1', 10);
        INSERT INTO news_meta VALUES (2, 'category_id', '3', 10);
        INSERT INTO news_meta VALUES (3, 'category_id', '8', 11);   -- قسم محذوف
        INSERT INTO news_meta VALUES (4, 'category_id', '1', 999);  -- خبر محذوف

        INSERT INTO publications VALUES (427, '2019-07-28', '', '', '', '', 1, 7, '1564485299', 0, NULL, '2019-07-28', 0, '');
        INSERT INTO publications VALUES (606, '2025-04-20', '', '', '', '', 1, 7, '1745000000', 0, NULL, '2025-04-20', 0, '');

        INSERT INTO setting VALUES (1, 'site_title', 'النجاة');
        INSERT INTO setting VALUES (2, 'box_category_1', '1');
        INSERT INTO setting VALUES (3, 'box_limit_1', '2');
        INSERT INTO setting VALUES (4, 'box_type_1', '1');
        INSERT INTO setting VALUES (5, 'box_banner_2', '2');
        INSERT INTO setting VALUES (6, 'pdf_box_category_1', '3');
        INSERT INTO setting VALUES (7, 'pdf_box_limit_1', '50');
        INSERT INTO setting VALUES (8, 'box_category_3', '8');     -- قسم محذوف

        INSERT INTO upload VALUES (1, 'صورة', 'http://www.alnajat.info/upload/upload_1.jpg', 7, '1566297335', '1024', 'jpg', 'image/jpeg');
    SQL);

    return $pdo;
}

function runImport(PDO $legacy): App\Legacy\ImportReport
{
    return (new LegacyImporter($legacy, DB::connection()->getPdo(), app(LegacyTransform::class)))->run(fresh: true);
}

beforeEach(function () {
    // نطاقات الموقع القديم ثابتة هنا، لا من LEGACY_HOSTS في .env الخادم الذي تُشغَّل عليه الاختبارات
    config(['alnajat.legacy_hosts' => ['alnajat.info', 'www.alnajat.info']]);
    $this->app->forgetInstance(LegacyTransform::class);
});

it('copies every row and keeps the ids', function () {
    runImport(legacyDatabase());

    expect(User::pluck('id')->sort()->values()->all())->toBe([6, 7])
        ->and(News::pluck('id')->sort()->values()->all())->toBe([10, 11])
        ->and(Publication::pluck('id')->sort()->values()->all())->toBe([427, 606]);
});

it('keeps each member able to log in', function () {
    runImport(legacyDatabase());

    $alaa = User::find(7);
    expect($alaa->legacy_password)->toBe('5ebe2294ecd0e0f08eab7690d2a6ee69')
        ->and($alaa->password)->toBeNull()
        ->and($alaa->role)->toBe('admin')
        ->and($alaa->is_active)->toBeTrue()
        ->and(User::find(6)->is_active)->toBeFalse();
});

it('makes image paths relative but leaves the article text untouched', function () {
    runImport(legacyDatabase());

    $news = News::find(10);
    expect($news->image)->toBe('upload/news_10.png')
        ->and($news->body)->toBe('<p><img src="http://www.alnajat.info/upload/x.jpg"></p>')
        ->and($news->created_by)->toBeNull()
        ->and($news->legacy_user_id)->toBe(1)
        ->and($news->updated_by)->toBe(7)
        ->and($news->newspaper_id)->toBe(4)
        ->and($news->published_date->toDateString())->toBe('2019-07-28');

    $empty = News::find(11);
    expect($empty->newspaper_id)->toBeNull()
        ->and($empty->published_date)->toBeNull();
});

it('links news to categories and reports the broken links', function () {
    $report = runImport(legacyDatabase());

    expect(News::find(10)->categories->pluck('id')->sort()->values()->all())->toBe([1, 3])
        ->and(News::find(11)->categories)->toHaveCount(0)
        ->and($report->skippedCount('category_news'))->toBe(2);
});

it('sets the pdf template version from the publication number', function () {
    runImport(legacyDatabase());

    expect(Publication::find(427)->pdf_version)->toBe(1)
        ->and(Publication::find(606)->pdf_version)->toBe(5);
});

it('splits the settings into plain settings and home boxes', function () {
    runImport(legacyDatabase());

    expect(Setting::get('site_title'))->toBe('النجاة')
        ->and(Setting::where('key', 'like', '%box_%')->count())->toBe(0)
        ->and(HomeBox::count())->toBe(30);

    $first = HomeBox::for('home')->first();
    expect($first->category_id)->toBe(1)->and($first->items_limit)->toBe(2)->and($first->type)->toBe(1);
    expect(HomeBox::where(['context' => 'home', 'position' => 2])->value('banner_id'))->toBe(2);
    expect(HomeBox::where(['context' => 'home', 'position' => 3])->value('category_id'))->toBeNull();
    expect(HomeBox::where(['context' => 'pdf', 'position' => 1])->value('items_limit'))->toBe(50);
});

it('can run again from scratch', function () {
    $legacy = legacyDatabase();
    runImport($legacy);
    runImport($legacy);

    expect(News::count())->toBe(2)->and(HomeBox::count())->toBe(30);
});

it('passes its own verification, and notices a change', function () {
    $legacy = legacyDatabase();
    runImport($legacy);

    $verifier = new LegacyVerifier($legacy, DB::connection()->getPdo(), app(LegacyTransform::class));
    $verifier->run();
    expect($verifier->passed())->toBeTrue();

    News::whereKey(10)->update(['body' => 'تغيّر']);
    $verifier->run();
    expect($verifier->passed())->toBeFalse();
});
