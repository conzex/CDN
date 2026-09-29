<?php

namespace Tests\Unit;

use App\Exceptions\ForbiddenExtensionException;
use App\Exceptions\InvalidPathException;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

use Tests\TestCase;

class FileServiceTest extends TestCase
{
    protected FileService $fileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileService = new FileService();
    }

    public function test_path_traversal_is_rejected(): void
    {
        $this->expectException(InvalidPathException::class);
        $this->fileService->sanitizePath('../secret.txt');
    }

    public function test_backslash_path_traversal_is_rejected(): void
    {
        $this->expectException(InvalidPathException::class);
        $this->fileService->sanitizePath('..\\secret.txt');
    }

    public function test_null_bytes_in_path_are_rejected(): void
    {
        $this->expectException(InvalidPathException::class);
        $this->fileService->sanitizePath("test.png\0.php");
    }

    public function test_dangerous_extensions_are_rejected(): void
    {
        $this->expectException(ForbiddenExtensionException::class);
        $file = UploadedFile::fake()->create('malicious.php', 100);
        $this->fileService->upload($file);
    }

    public function test_filename_sanitization(): void
    {
        $sanitized = $this->fileService->sanitizeName('My Test File (1)!.jpg');
        $this->assertEquals('My-Test-File-1.jpg', $sanitized);
    }

    public function test_list_hides_dotfiles_and_trash(): void
    {
        $testDir = public_path('test_list_dir');
        File::makeDirectory($testDir, 0755, true);
        File::put($testDir . '/.env', 'SECRET=123');
        File::put($testDir . '/normal.txt', 'Hello World');

        try {
            $list = $this->fileService->list('test_list_dir');
            $names = array_column($list, 'name');

            $this->assertContains('normal.txt', $names);
            $this->assertNotContains('.env', $names);
        } finally {
            File::deleteDirectory($testDir);
        }
    }

    public function test_public_url_generation(): void
    {
        $url = $this->fileService->publicUrl('bg/dc.jpg');
        $this->assertEquals(url('/bg/dc.jpg'), $url);
    }
}
