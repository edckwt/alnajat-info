<?php

use App\Legacy\LegacyTransform;

function legacyTransform(): LegacyTransform
{
    return new LegacyTransform(
        legacyHosts: ['alnajat.info', 'www.alnajat.info'],
        pdfVersions: [427 => 1, 541 => 2, 590 => 3, 605 => 4],
        pdfLatestVersion: 5,
        timezone: 'Asia/Kuwait',
    );
}

it('turns legacy file urls into relative paths', function (string $url) {
    expect(legacyTransform()->localPath($url))->toBe('upload/news_1.png');
})->with([
    'http://alnajat.info/upload/news_1.png',
    'http://www.alnajat.info/upload/news_1.png',
    'https://www.alnajat.info/upload/news_1.png',
    'HTTPS://ALNAJAT.INFO/upload/news_1.png',
]);

it('keeps external urls and empty values as they are', function () {
    expect(legacyTransform()->localPath('https://ipc.org.kw/a.jpg'))->toBe('https://ipc.org.kw/a.jpg')
        ->and(legacyTransform()->localPath(''))->toBeNull()
        ->and(legacyTransform()->localPath(null))->toBeNull();
});

it('picks the same pdf template version as pdfVersion() did', function (int $id, int $version) {
    expect(legacyTransform()->pdfVersion($id))->toBe($version);
})->with([
    [1, 1], [427, 1], [428, 2], [541, 2], [542, 3], [590, 3], [591, 4], [605, 4], [606, 5], [2000, 5],
]);

it('converts unix timestamps stored as text to Kuwait time', function () {
    expect(legacyTransform()->timestamp('1557913294'))->toBe('2019-05-15 12:41:34')
        ->and(legacyTransform()->timestamp(''))->toBeNull()
        ->and(legacyTransform()->timestamp('0'))->toBeNull()
        ->and(legacyTransform()->timestamp('abc'))->toBeNull();
});

it('keeps only valid dates', function () {
    expect(legacyTransform()->date('2019-07-28'))->toBe('2019-07-28')
        ->and(legacyTransform()->date('2019-02-30'))->toBeNull()
        ->and(legacyTransform()->date(''))->toBeNull();
});

it('gives the old full-access group the admin role', function () {
    expect(legacyTransform()->role(0))->toBe('admin')
        ->and(legacyTransform()->role(2))->toBe('editor');
});
