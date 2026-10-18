<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Campus Events')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/campus.css') }}" rel="stylesheet">
</head>
<body data-page="@yield('page')" @yield('body-attributes')>
    <a class="skip-link" href="#content">Skip to content</a>
    <header class="site-header">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}">Campus Events</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#site-nav" aria-controls="site-nav" aria-expanded="false" aria-label="Open menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="site-nav">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2" id="nav-links">
                        <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Events</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main id="content" class="container py-4">
        <div id="page-alert" class="alert d-none" role="alert"></div>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container py-4">
            <p class="mb-0">Northrise University campus events. Event, account, and registration data is loaded from the versioned API in your browser.</p>
        </div>
    </footer>

    <noscript>
        <div class="container pb-4">
            <p class="alert alert-warning">Enable JavaScript to browse events and sign in. This page does not include event records in the HTML.</p>
        </div>
    </noscript>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/campus.js') }}"></script>
</body>
</html>
