<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    protected array $allowedMimes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    public function store(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:' . implode(',', $this->allowedMimes),
            ],
        ]);

        $file = $request->file('file');
        $uuid = Str::uuid();
        $extension = $file->guessExtension() ?: 'bin';
        $filename = "{$uuid}.{$extension}";

        $path = $file->storeAs('uploads/temp', $filename, 'local');

        // Track uploads in session so only this user can reference/delete them
        $sessionUploads = $request->session()->get('pending_uploads', []);
        $sessionUploads[] = $filename;
        $request->session()->put('pending_uploads', $sessionUploads);

        return response()->json([
            'success' => true,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }

    public function destroy(Request $request, string $filename)
    {
        // Validate filename format (UUID.ext)
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.\w+$/', $filename)) {
            abort(400, 'Invalid filename.');
        }

        // Only allow deletion of files uploaded in this session
        $sessionUploads = $request->session()->get('pending_uploads', []);
        if (!in_array($filename, $sessionUploads)) {
            abort(403, 'Not your upload.');
        }

        $path = "uploads/temp/{$filename}";
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        $request->session()->put('pending_uploads', array_values(array_diff($sessionUploads, [$filename])));

        return response()->json(['success' => true]);
    }
}
