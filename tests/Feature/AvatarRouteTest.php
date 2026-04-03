<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_avatar_route_is_reachable()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/profile/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(200);
    }
}
