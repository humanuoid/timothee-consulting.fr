<?php

declare(strict_types=1);

use App\Domain\Entity\Page;
use App\Domain\Entity\PageContent;
use App\Domain\Entity\PageModel;
use App\Domain\Entity\User;
use App\Domain\I18n\Locales;

function a_page(): Page
{
    return Page::create(
        PageModel::create('One-page', 'one-page', ['header']),
        User::create('editor@timothee-consulting.fr', 'hash', ['ROLE_EDITOR'], 'Timothée', Locales::FR)
    );
}

test('page content rejects an unknown locale', function () {
    expect(fn() => PageContent::create(a_page(), 'de', 'accueil', 'Accueil', 'Accueil', 'Portfolio'))
        ->toThrow(\InvalidArgumentException::class);
});

test('publish copies the draft and stamps the publication date', function () {
    $content = PageContent::create(a_page(), Locales::FR, 'accueil', 'Accueil', 'Accueil', 'Portfolio');

    expect($content->isPublished())
        ->toBeFalse()
        ->and($content->published)
        ->toBeNull()
        ->and($content->publishedAt)
        ->toBeNull();

    $content->publish();

    expect($content->isPublished())
        ->toBeTrue()
        ->and($content->published)
        ->toBe($content->draft)
        ->and($content->publishedAt)
        ->toBeInstanceOf(\DateTimeImmutable::class);
});
