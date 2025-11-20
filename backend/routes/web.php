<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// Serve React app for all frontend routes (SPA)
// This should be last to catch all non-API routes
Route::get('/{any}', function () {
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return file_get_contents($indexPath);
    }
    return response()->json(['error' => 'Frontend not built'], 404);
})->where('any', '^(?!api).*$');
