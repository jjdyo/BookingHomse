<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    /**
     * Serve files from the public storage disk without relying on the public/storage symlink.
     * Restrict access to known-safe directories (e.g., logos/) to avoid exposing arbitrary files.
     */
    public function public(string $path, Request $request)
    {
        // Normalize path
        $path = ltrim($path, '/');

        // Basic security: disallow path traversal and restrict to whitelisted prefixes
        if (str_contains($path, '..')) {
            abort(403);
        }

        $allowedPrefixes = [
            'logos/', // site-config uploaded logos
        ];

        $isAllowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            abort(403);
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        // Stream the file with appropriate headers
        return Storage::disk('public')->response($path);
    }
}
