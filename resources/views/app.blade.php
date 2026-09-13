<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
              rel="stylesheet" />
        <!-- Scripts -->
        @routes
        @vite([
            'resources/js/app.js',
            "resources/js/Pages/{$page['component']}.vue"
        ])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        <div id="page-skeleton" class="page-skeleton">
            <!-- NAVBAR -->
            <div class="skeleton-navbar">
                <div class="skeleton-logo"></div>
                <div class="skeleton-nav-item"></div>
                <div class="skeleton-nav-item"></div>
                <div class="skeleton-nav-item"></div>
                <div class="skeleton-nav-item"></div>
                <div class="skeleton-nav-item"></div>
                <div class="skeleton-nav-item"></div>
                <div class="skeleton-nav-item"></div>
                <div class="skeleton-admin"></div>
            </div>
            <!-- PAGE HEADER -->
            <div class="skeleton-page-header">
                <div class="skeleton-title"></div>
            </div>
            <!-- DASHBOARD CONTENT -->
            <div class="skeleton-content">
                <div class="skeleton-statistics">
                    <div class="skeleton-stat-card">
                        <div class="skeleton-stat-number"></div>
                        <div class="skeleton-stat-title"></div>
                        <div class="skeleton-stat-subtitle"></div>
                    </div>
                    <div class="skeleton-stat-card">
                        <div class="skeleton-stat-number"></div>
                        <div class="skeleton-stat-title"></div>
                        <div class="skeleton-stat-subtitle"></div>
                    </div>
                    <div class="skeleton-stat-card">
                        <div class="skeleton-stat-number"></div>
                        <div class="skeleton-stat-title"></div>
                        <div class="skeleton-stat-subtitle"></div>
                    </div>
                    <div class="skeleton-stat-card">
                        <div class="skeleton-stat-number"></div>
                        <div class="skeleton-stat-title"></div>
                        <div class="skeleton-stat-subtitle"></div>
                    </div>
                </div>
                <div class="skeleton-section">
                    <div class="skeleton-section-title"></div>
                    <div class="skeleton-recommendation">
                        <div class="skeleton-recommendation-row">
                            <div class="skeleton-recommendation-label"></div>
                            <div class="skeleton-progress"></div>
                        </div>
                        <div class="skeleton-recommendation-row">
                            <div class="skeleton-recommendation-label"></div>
                            <div class="skeleton-progress short"></div>
                        </div>
                    </div>
                </div>
                <div class="skeleton-section">
                    <div class="skeleton-section-title"></div>
                    <div class="skeleton-activity">
                        @for ($i = 0; $i < 5; $i++)
                            <div class="skeleton-activity-item">
                                <div class="skeleton-user"></div>
                                <div class="skeleton-user-role"></div>
                                <div class="skeleton-status"></div>
                                <div class="skeleton-date"></div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
        @inertia
    </body>

</html>