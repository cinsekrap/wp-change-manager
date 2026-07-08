<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_video_upload_is_accepted(): void
    {
        $videos = [
            'clip.mp4' => 'video/mp4',
            'clip.mov' => 'video/quicktime',
            'clip.webm' => 'video/webm',
            'clip.avi' => 'video/x-msvideo',
        ];

        foreach ($videos as $name => $mime) {
            $response = $this->post(route('api.upload'), [
                'file' => UploadedFile::fake()->create($name, 1024, $mime),
            ]);

            $response->assertOk()->assertJson(['success' => true]);
        }
    }

    public function test_document_upload_is_accepted(): void
    {
        $response = $this->post(route('api.upload'), [
            'file' => UploadedFile::fake()->create('doc.pdf', 512, 'application/pdf'),
        ]);

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_disallowed_type_is_rejected(): void
    {
        $response = $this->post(route('api.upload'), [
            'file' => UploadedFile::fake()->create('archive.zip', 512, 'application/zip'),
        ]);

        $response->assertSessionHasErrors('file');
    }
}
