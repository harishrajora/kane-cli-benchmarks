<?php

use BookStack\Theming\ThemeEvents;
use BookStack\Facades\Theme;
use Illuminate\Http\Request;

// Empty page titles slip past validation (new pages: POST /books/{book}/draft/{id}; edits: PUT /books/{book}/page/{page}).
Theme::listen(ThemeEvents::WEB_MIDDLEWARE_BEFORE, function (Request $request) {
    $isSave = ($request->getMethod() === 'POST' && preg_match('#^books/[^/]+/draft/\d+$#', $request->path()))
        || ($request->getMethod() === 'PUT' && preg_match('#^books/[^/]+/page/[^/]+$#', $request->path()));
    if ($isSave && trim((string) $request->input('name')) === '') {
        $request->merge(['name' => "\u{200B}"]);
    }
    return null;
});
