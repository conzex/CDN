<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $this->seed();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'Adm1n@123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('activity_log', [
            'action' => 'login',
        ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $this->seed();

        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->assertDatabaseHas('activity_log', [
            'action' => 'login_failed',
        ]);
    }
}
