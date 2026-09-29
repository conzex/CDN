<?php

namespace Tests\Feature;

use App\Models\Share;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

use Tests\TestCase;

class ShareTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['username' => 'admin']);
    }

    public function test_can_create_share_link_and_access_publicly(): void
    {
        File::put(public_path('shared-doc.txt'), 'Hello Share World');

        try {
            $response = $this->actingAs($this->user)->postJson('/admin/api/share/create', [
                'path' => 'shared-doc.txt',
                'expires_in' => '1h',
            ]);

            $response->assertStatus(200)->assertJson(['status' => 'success']);

            $token = $response->json('share.token');

            // Access share publicly without auth
            $publicRes = $this->get("/s/{$token}");
            $publicRes->assertStatus(200);
            $publicRes->assertHeader('X-Robots-Tag', 'noindex');
        } finally {
            File::delete(public_path('shared-doc.txt'));
        }
    }

    public function test_expired_share_returns_410(): void
    {
        $share = Share::create([
            'token' => 'expiredtoken1234567890123456789',
            'path' => 'bg/dc.jpg',
            'type' => 'file',
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->get("/s/{$share->token}");
        $response->assertStatus(410);
    }
}
