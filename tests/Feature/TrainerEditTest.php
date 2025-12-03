<?php

namespace Tests\Feature;

use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainerEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_trainer_and_replace_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $trainer = Trainer::factory()->create([
            'name' => 'Alex',
            'title' => 'Assistant',
            'bio' => 'Bio',
            'photo_path' => null,
        ]);

        $this->actingAs($user);

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        // Use POST with _method=PUT (multipart)
        $response = $this->post("/trainers/{$trainer->id}", [
            '_method' => 'PUT',
            'name' => 'Alexandra',
            'title' => 'Head Trainer',
            'bio' => 'Updated bio',
            'photo' => $file,
        ]);

        $response->assertRedirect(route('dashboard.trainers'));

        $trainer->refresh();
        $this->assertSame('Alexandra', $trainer->name);
        $this->assertSame('Head Trainer', $trainer->title);
        $this->assertSame('Updated bio', $trainer->bio);
        $this->assertNotNull($trainer->photo_path);
        Storage::disk('public')->assertExists($trainer->photo_path);
    }
}
