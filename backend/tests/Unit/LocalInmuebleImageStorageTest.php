<?php

namespace Tests\Unit;

use App\Contracts\InmuebleImageStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class LocalInmuebleImageStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_storage_falls_back_to_public_disk_without_firebase_bucket(): void
    {
        Storage::fake('public');
        config(['services.firebase.images_bucket' => null]);

        $storage = app(InmuebleImageStorage::class);
        $path = 'inmuebles/1/local-test.png';

        $url = $storage->upload(
            UploadedFile::fake()->image('local-test.png'),
            $path,
            'image/png',
        );

        Storage::disk('public')->assertExists($path);
        self::assertSame(Storage::disk('public')->url($path), $url);

        $storage->delete($path);
        Storage::disk('public')->assertMissing($path);
    }
}
