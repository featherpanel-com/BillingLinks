<?php

/*
 * This file is part of FeatherPanel.
 *
 * Copyright (C) 2025 MythicalSystems Studios
 * Copyright (C) 2025 FeatherPanel Contributors
 * Copyright (C) 2025 Cassian Gherman (aka NaysKutzu)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See the LICENSE file or <https://www.gnu.org/licenses/>.
 */

use App\App;
use App\Permissions;
use App\Helpers\ApiResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use App\Addons\billinglinks\Controllers\User\BillingLinksController as UserController;
use App\Addons\billinglinks\Controllers\Admin\BillingLinksController as AdminController;

return function (RouteCollection $routes): void {
    // User Routes (require authentication)

    // Get available providers
    App::getInstance(true)->registerAuthRoute(
        $routes,
        'billinglinks-user-providers',
        '/api/user/billinglinks/providers',
        function (Request $request) {
            return (new UserController())->getProviders($request);
        },
        ['GET']
    );

    // Get link history
    App::getInstance(true)->registerAuthRoute(
        $routes,
        'billinglinks-user-history',
        '/api/user/billinglinks/history',
        function (Request $request) {
            return (new UserController())->getHistory($request);
        },
        ['GET']
    );

    // Start a link (redirect to provider)
    App::getInstance(true)->registerAuthRoute(
        $routes,
        'billinglinks-user-start',
        '/api/user/billinglinks/start/{provider}',
        function (Request $request, array $args) {
            $provider = $args['provider'] ?? '';
            if (empty($provider)) {
                return ApiResponse::error('Provider is required', 'PROVIDER_REQUIRED', 400);
            }

            return (new UserController())->startLink($request, $provider);
        },
        ['GET']
    );

    // Complete a link and earn credits
    App::getInstance(true)->registerAuthRoute(
        $routes,
        'billinglinks-user-earn',
        '/api/user/billinglinks/earn/{code}',
        function (Request $request, array $args) {
            $code = $args['code'] ?? '';
            if (empty($code)) {
                return ApiResponse::error('Code is required', 'CODE_REQUIRED', 400);
            }

            return (new UserController())->earnLink($request, $code);
        },
        ['GET']
    );

    // Admin Routes

    // Get settings
    App::getInstance(true)->registerAdminRoute(
        $routes,
        'billinglinks-admin-settings',
        '/api/admin/billinglinks/settings',
        function (Request $request) {
            return (new AdminController())->getSettings($request);
        },
        Permissions::ADMIN_USERS_VIEW,
        ['GET']
    );

    // Update settings
    App::getInstance(true)->registerAdminRoute(
        $routes,
        'billinglinks-admin-settings-update',
        '/api/admin/billinglinks/settings',
        function (Request $request) {
            return (new AdminController())->updateSettings($request);
        },
        Permissions::ADMIN_USERS_EDIT,
        ['PATCH', 'PUT']
    );

    // Get all links
    App::getInstance(true)->registerAdminRoute(
        $routes,
        'billinglinks-admin-links',
        '/api/admin/billinglinks/links',
        function (Request $request) {
            return (new AdminController())->getLinks($request);
        },
        Permissions::ADMIN_USERS_VIEW,
        ['GET']
    );
};
