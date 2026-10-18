<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EventBannerStore
{
    /**
     * Store a banner on the public disk only.
     *
     * Uploaded files receive a generated name under events/banners.
     * A supplied path is accepted only when it matches that folder and the file is already there.
     */
    public function attach(Event $event, ?UploadedFile $file, ?string $path): void
    {
        if ($file !== null) {
            $stored = $file->store('events/banners', 'public');

            if ($stored === false) {
                throw ValidationException::withMessages([
                    'banner' => 'The banner could not be stored.',
                ]);
            }

            $event->images()->create([
                'disk' => 'public',
                'path' => $stored,
                'original_name' => $this->safeOriginalName($file->getClientOriginalName()),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'is_banner' => true,
            ]);

            return;
        }

        if ($path === null || $path === '') {
            return;
        }

        $this->assertSafePublicPath($path);

        if (! Storage::disk('public')->exists($path)) {
            throw ValidationException::withMessages([
                'banner_path' => 'The banner file does not exist in public event storage.',
            ]);
        }

        $event->images()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => basename($path),
            'mime_type' => Storage::disk('public')->mimeType($path) ?: null,
            'size_bytes' => Storage::disk('public')->size($path) ?: null,
            'is_banner' => true,
        ]);
    }

    private function assertSafePublicPath(string $path): void
    {
        if (preg_match('#^events/banners/[A-Za-z0-9][A-Za-z0-9._-]{0,180}$#', $path) !== 1) {
            throw ValidationException::withMessages([
                'banner_path' => 'The banner path is not allowed.',
            ]);
        }
    }

    private function safeOriginalName(string $name): string
    {
        $base = basename(str_replace('\\', '/', $name));
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $base) ?: 'banner';

        return substr($safe, 0, 255);
    }
}
