<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves uploaded images (uniform/cloth photos) through an `api/*` path so the
 * global HandleCors middleware attaches CORS headers to the response. The
 * files physically live in public/uploads and are normally served as static
 * files - but static files bypass Laravel middleware, so Flutter web
 * (CanvasKit draws images onto a canvas with crossOrigin=anonymous) can't load
 * them cross-origin without those headers. Native apps don't need this, but
 * routing image URLs here makes them work identically on web and device.
 */
class AssetController extends Controller
{
    public function upload(string $path): BinaryFileResponse
    {
        // The stored value may be a bare filename or include an `uploads/`
        // prefix; normalise to a filename under public/uploads and block any
        // path-traversal attempt.
        $path = preg_replace('#^/*uploads/#', '', ltrim($path, '/'));
        abort_if($path === '' || str_contains($path, '..'), 404);

        $full = public_path('uploads/' . $path);
        abort_unless(is_file($full), 404);

        return response()->file($full);
    }

    /**
     * Builds the CORS-friendly URL for a stored `*_photo` value (or null when
     * there is no photo). Reused by the uniform/cart/order controllers so the
     * image URL is generated one way everywhere.
     */
    public static function urlFor(?string $photo): ?string
    {
        if (empty($photo)) {
            return null;
        }

        return url('api/uploads/' . basename($photo));
    }
}
