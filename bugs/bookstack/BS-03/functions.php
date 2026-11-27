<?php

use BookStack\Theming\ThemeEvents;
use BookStack\Facades\Theme;
use Illuminate\Http\Request;

// Page updates (PUT /books/{book}/page/{page}) report success without saving.
Theme::listen(ThemeEvents::WEB_MIDDLEWARE_BEFORE, function (Request $request) {
    if ($request->getMethod() === 'PUT' && preg_match('#^books/[^/]+/page/[^/]+$#', $request->path())) {
        session()->flash('success', trans('activities.page_update_notification'));
        return redirect('/' . $request->path());
    }
    return null;
});
