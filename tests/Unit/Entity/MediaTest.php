<?php

declare(strict_types=1);

use App\Domain\Entity\Media;

test('media cannot be created without an alt text', function () {
    expect(fn() => Media::create('photo.jpg', '   ', '/media/photo.jpg', 'image/jpeg', 2048))
        ->toThrow(\InvalidArgumentException::class);
});

test('media exposes its metadata', function () {
    $media = Media::create('photo.jpg', 'Portrait de Timothée', '/media/photo.jpg', 'image/jpeg', 2048);

    expect($media->fileName)
        ->toBe('photo.jpg')
        ->and($media->altText)
        ->toBe('Portrait de Timothée')
        ->and($media->path)
        ->toBe('/media/photo.jpg')
        ->and($media->mimeType)
        ->toBe('image/jpeg')
        ->and($media->size)
        ->toBe(2048);
});
