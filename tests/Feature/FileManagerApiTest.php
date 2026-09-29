<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

use Tests\TestCase;

class FileManagerApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['username' => 'admin']);
    }

    public function test_unauthenticated_user_cannot_access_api(): void
    {
        $response = $this->getJson('/admin/api/list');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_files(): void
    {
        $response = $this->actingAs($this->user)->getJson('/admin/api/list');
        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_user_can_create_and_delete_folder(): void
    {
        $response = $this->actingAs($this->user)->postJson('/admin/api/folder', [
            'name' => 'test-folder-api',
            'path' => '',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDirectoryExists(public_path('test-folder-api'));

        // Delete folder
        $delResponse = $this->actingAs($this->user)->postJson('/admin/api/delete', [
            'path' => 'test-folder-api',
        ]);

        $delResponse->assertStatus(200);
        $this->assertDirectoryDoesNotExist(public_path('test-folder-api'));
    }

    public function test_user_can_upload_valid_file(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500);

        $response = $this->actingAs($this->user)->post('/admin/api/upload', [
            'files' => [$file],
            'path' => '',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertFileExists(public_path('document.pdf'));

        // Cleanup
        File::delete(public_path('document.pdf'));
    }
}
