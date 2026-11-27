<?php

use BookStack\Theming\ThemeEvents;
use BookStack\Facades\Theme;
use Illuminate\Http\Request;

// Onboarding's "Getting Started" page leads to the API Guide's page of the same name.
Theme::listen(ThemeEvents::WEB_MIDDLEWARE_BEFORE, function (Request $request) {
    if ($request->isMethod('GET') && $request->path() === 'books/onboarding/page/getting-started') {
        return redirect('/books/api-guide/page/getting-started');
    }
    return null;
});
