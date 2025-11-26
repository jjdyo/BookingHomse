<?php

namespace Tests\Feature\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_serves_existing_logo_file(): void
    {
        Storage::fake('public');

        $path = 'logos/example.jpg';
        Storage::disk('public')->put($path, 'fake-image-bytes');

        $res = $this->get(route('media.public', ['path' => $path]));

        $res->assertOk();
        $res->assertHeader('Content-Type');
        // We don't assert the body content here because Storage::fake may not return a real file stream.
    }

    public function test_missing_file_returns_404(): void
    {
        Storage::fake('public');
        $res = $this->get(route('media.public', ['path' => 'logos/missing.png']));
        $res->assertNotFound();
    }

    public function test_disallows_path_traversal_and_disallowed_prefix(): void
    {
        Storage::fake('public');
        // Path traversal
        $this->get('/media/../.env')->assertForbidden();

        // Disallowed prefix
        $this->get('/media/otherdir/file.txt')->assertForbidden();
    }
}
