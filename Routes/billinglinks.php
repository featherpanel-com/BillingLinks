<?php

/*
 * This file is part of FeatherPanel.
 *
 * MIT License
 *
 * Copyright (c) 2025 MythicalSystems
 * Copyright (c) 2025 Cassian Gherman (NaysKutzu)
 * Copyright (c) 2018 - 2021 Dane Everitt <dane@daneeveritt.com> and Contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
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
