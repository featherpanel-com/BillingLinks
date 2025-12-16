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

namespace App\Addons\billinglinks\Controllers\Admin;

use App\Chat\Activity;
use App\Helpers\ApiResponse;
use OpenApi\Attributes as OA;
use App\Plugins\PluginSettings;
use App\CloudFlare\CloudFlareRealIP;
use App\Addons\billinglinks\Chat\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Addons\billinglinks\Helpers\LinksHelper;

#[OA\Tag(name: 'Admin - Billing Links', description: 'Links4Rewards management for administrators')]
class BillingLinksController
{
    /**
     * Get L4R settings.
     */
    #[OA\Get(
        path: '/api/admin/billinglinks/settings',
        summary: 'Get L4R settings',
        description: 'Get all Links4Rewards settings',
        tags: ['Admin - Billing Links'],
        responses: [
            new OA\Response(response: 200, description: 'Settings retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function getSettings(Request $request): Response
    {
        $settings = LinksHelper::getSettings();

        return ApiResponse::success($settings, 'Settings retrieved successfully', 200);
    }

    /**
     * Update L4R settings.
     */
    #[OA\Patch(
        path: '/api/admin/billinglinks/settings',
        summary: 'Update L4R settings',
        description: 'Update Links4Rewards settings',
        tags: ['Admin - Billing Links'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'l4r_enabled', type: 'string', enum: ['true', 'false']),
                    new OA\Property(property: 'l4r_linkvertise_enabled', type: 'string', enum: ['true', 'false']),
                    new OA\Property(property: 'l4r_linkvertise_user_id', type: 'string'),
                    new OA\Property(property: 'l4r_linkvertise_coins_per_link', type: 'string'),
                    new OA\Property(property: 'l4r_linkvertise_daily_limit', type: 'string'),
                    new OA\Property(property: 'l4r_linkvertise_min_time_to_complete', type: 'string'),
                    new OA\Property(property: 'l4r_linkvertise_time_to_expire', type: 'string'),
                    new OA\Property(property: 'l4r_linkvertise_cooldown_time', type: 'string'),
                    new OA\Property(property: 'l4r_shareus_enabled', type: 'string', enum: ['true', 'false']),
                    new OA\Property(property: 'l4r_shareus_api_key', type: 'string'),
                    new OA\Property(property: 'l4r_shareus_coins_per_link', type: 'string'),
                    new OA\Property(property: 'l4r_shareus_daily_limit', type: 'string'),
                    new OA\Property(property: 'l4r_shareus_min_time_to_complete', type: 'string'),
                    new OA\Property(property: 'l4r_shareus_time_to_expire', type: 'string'),
                    new OA\Property(property: 'l4r_shareus_cooldown_time', type: 'string'),
                    new OA\Property(property: 'l4r_linkpays_enabled', type: 'string', enum: ['true', 'false']),
                    new OA\Property(property: 'l4r_linkpays_api_key', type: 'string'),
                    new OA\Property(property: 'l4r_linkpays_coins_per_link', type: 'string'),
                    new OA\Property(property: 'l4r_linkpays_daily_limit', type: 'string'),
                    new OA\Property(property: 'l4r_linkpays_min_time_to_complete', type: 'string'),
                    new OA\Property(property: 'l4r_linkpays_time_to_expire', type: 'string'),
                    new OA\Property(property: 'l4r_linkpays_cooldown_time', type: 'string'),
                    new OA\Property(property: 'l4r_gyanilinks_enabled', type: 'string', enum: ['true', 'false']),
                    new OA\Property(property: 'l4r_gyanilinks_api_key', type: 'string'),
                    new OA\Property(property: 'l4r_gyanilinks_coins_per_link', type: 'string'),
                    new OA\Property(property: 'l4r_gyanilinks_daily_limit', type: 'string'),
                    new OA\Property(property: 'l4r_gyanilinks_min_time_to_complete', type: 'string'),
                    new OA\Property(property: 'l4r_gyanilinks_time_to_expire', type: 'string'),
                    new OA\Property(property: 'l4r_gyanilinks_cooldown_time', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Settings updated successfully'),
            new OA\Response(response: 400, description: 'Invalid input'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function updateSettings(Request $request): Response
    {
        $admin = $request->attributes->get('user') ?? $request->get('user');
        if (!$admin || !isset($admin['id'])) {
            return ApiResponse::error('Admin not authenticated', 'UNAUTHORIZED', 401);
        }

        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ApiResponse::error('Invalid JSON in request body', 'INVALID_JSON', 400);
        }

        $allowedKeys = [
            'l4r_enabled',
            'l4r_linkvertise_enabled',
            'l4r_linkvertise_user_id',
            'l4r_linkvertise_coins_per_link',
            'l4r_linkvertise_daily_limit',
            'l4r_linkvertise_min_time_to_complete',
            'l4r_linkvertise_time_to_expire',
            'l4r_linkvertise_cooldown_time',
            'l4r_shareus_enabled',
            'l4r_shareus_api_key',
            'l4r_shareus_coins_per_link',
            'l4r_shareus_daily_limit',
            'l4r_shareus_min_time_to_complete',
            'l4r_shareus_time_to_expire',
            'l4r_shareus_cooldown_time',
            'l4r_linkpays_enabled',
            'l4r_linkpays_api_key',
            'l4r_linkpays_coins_per_link',
            'l4r_linkpays_daily_limit',
            'l4r_linkpays_min_time_to_complete',
            'l4r_linkpays_time_to_expire',
            'l4r_linkpays_cooldown_time',
            'l4r_gyanilinks_enabled',
            'l4r_gyanilinks_api_key',
            'l4r_gyanilinks_coins_per_link',
            'l4r_gyanilinks_daily_limit',
            'l4r_gyanilinks_min_time_to_complete',
            'l4r_gyanilinks_time_to_expire',
            'l4r_gyanilinks_cooldown_time',
        ];

        $updated = [];
        foreach ($allowedKeys as $key) {
            if (isset($data[$key])) {
                $value = (string) $data[$key];
                PluginSettings::setSetting('billinglinks', $key, $value);
                $updated[$key] = $value;
            }
        }

        if (empty($updated)) {
            return ApiResponse::error('No valid settings provided', 'NO_SETTINGS', 400);
        }

        Activity::createActivity([
            'user_uuid' => $admin['uuid'] ?? null,
            'name' => 'billinglinks_settings_updated',
            'context' => 'Updated Links4Rewards settings',
            'ip_address' => CloudFlareRealIP::getRealIP(),
        ]);

        $settings = LinksHelper::getSettings();

        return ApiResponse::success($settings, 'Settings updated successfully', 200);
    }

    /**
     * Get all links with pagination.
     */
    #[OA\Get(
        path: '/api/admin/billinglinks/links',
        summary: 'Get all links',
        description: 'Get paginated list of all links',
        tags: ['Admin - Billing Links'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Links retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function getLinks(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $links = Link::getAll($limit, $offset);
        $total = Link::getCount();

        return ApiResponse::success([
            'data' => $links,
            'meta' => [
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
            ],
        ], 'Links retrieved successfully', 200);
    }
}
