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

namespace App\Addons\billinglinks\Controllers\User;

use App\App;
use App\Chat\Activity;
use App\Helpers\UUIDUtils;
use App\Helpers\ApiResponse;
use OpenApi\Attributes as OA;
use App\CloudFlare\CloudFlareRealIP;
use App\Addons\billinglinks\Chat\Link;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Addons\billinglinks\Helpers\LinksHelper;
use App\Addons\billingcore\Helpers\CreditsHelper;
use App\Addons\billinglinks\Services\ShareUS\ShareUS;
use App\Addons\billinglinks\Services\LinkPays\LinkPays;
use App\Addons\billinglinks\Services\GyaniLinks\GyaniLinks;

#[OA\Tag(name: 'User - Billing Links', description: 'Links4Rewards management for users')]
class BillingLinksController
{
    /**
     * Start a link (redirect to provider).
     * This endpoint handles GET requests and redirects users to the link provider.
     */
    public function startLink(Request $request, string $provider): Response
    {
        $user = $request->attributes->get('user') ?? $request->get('user');
        if (!$user || !isset($user['id'])) {
            return $this->renderErrorPage('User not authenticated');
        }

        if (!LinksHelper::isEnabled()) {
            return $this->renderErrorPage('Links4Rewards is currently disabled');
        }

        $provider = strtolower($provider);
        if (!LinksHelper::isProviderEnabled($provider)) {
            return $this->renderErrorPage('Provider is not enabled');
        }

        $settings = LinksHelper::getProviderSettings($provider);
        if (!$settings) {
            return $this->renderErrorPage('Invalid provider');
        }

        $dayLimit = (int) ($settings['daily_limit'] ?? 5);
        $coolDown = (int) ($settings['cooldown_time'] ?? 3600);

        // Check daily limit and cooldown
        $links = Link::getAllByUser($user['id'], 35);
        $dayCount = 0;
        $now = new \DateTime();
        $dayAgo = clone $now;
        $dayAgo->modify('-24 hours');

        foreach ($links as $link) {
            if ($link['provider'] !== $provider) {
                continue;
            }

            try {
                $createdAt = new \DateTime($link['created_at']);
            } catch (\Exception $e) {
                // Fallback to strtotime if DateTime fails
                $createdAt = \DateTime::createFromFormat('U', strtotime($link['created_at']));
                if (!$createdAt) {
                    continue; // Skip invalid timestamps
                }
            }

            if ($createdAt > $dayAgo) {
                ++$dayCount;
                $timeSinceLastLink = $now->getTimestamp() - $createdAt->getTimestamp();
                if ($timeSinceLastLink < $coolDown) {
                    $waitTime = $coolDown - $timeSinceLastLink;
                    $waitMinutes = ceil($waitTime / 60);

                    return $this->renderCooldownPage($waitMinutes);
                }
            }
        }

        if ($dayCount >= $dayLimit) {
            return $this->renderDailyLimitPage($dayLimit);
        }

        // Generate UUID and create link
        $linkUuid = UUIDUtils::generateV4();
        $linkId = Link::create($linkUuid, $user['id'], $provider);

        if ($linkId === 0) {
            return $this->renderErrorPage('Failed to create link code');
        }

        // Get app URL for callback
        $app = App::getInstance(true);
        $config = $app->getConfig();
        $appUrl = $config->getSetting('APP_URL', 'https://featherpanel.mythical.systems');
        if (strpos($appUrl, 'http') !== 0) {
            $appUrl = 'https://' . $appUrl;
        }

        $finalLink = $appUrl . '/api/user/billinglinks/earn/' . $linkUuid;

        Activity::createActivity([
            'user_uuid' => $user['uuid'] ?? null,
            'name' => 'link_started',
            'context' => "Started {$provider} link: {$linkUuid}",
            'ip_address' => CloudFlareRealIP::getRealIP(),
        ]);

        // Handle different providers
        if ($provider === 'linkvertise') {
            $userId = $settings['user_id'] ?? '583258';

            return $this->renderLinkvertisePage($linkUuid, $userId);
        }

        // For other providers, get shortened link and redirect
        try {
            $shortenedUrl = $this->getShortenedLink($provider, $finalLink, $settings);
            header('Location: ' . $shortenedUrl);
            exit;
        } catch (\Exception $e) {
            return $this->renderErrorPage('Failed to create ' . $provider . ' link: ' . $e->getMessage());
        }
    }

    /**
     * Complete a link and award credits.
     * This endpoint handles GET requests when users return from the link provider.
     */
    public function earnLink(Request $request, string $code): Response
    {
        $user = $request->attributes->get('user') ?? $request->get('user');
        if (!$user || !isset($user['id'])) {
            return $this->renderErrorPage('User not authenticated');
        }

        if (!LinksHelper::isEnabled()) {
            return $this->renderErrorPage('Links4Rewards is currently disabled');
        }

        // Validate code format
        if (!UUIDUtils::isValid($code)) {
            return $this->renderErrorPage('Invalid link code');
        }

        $linkId = Link::convertCodeToId($code);
        if ($linkId === 0) {
            return $this->renderErrorPage('Invalid link code');
        }

        $link = Link::getById($linkId);
        if (!$link) {
            return $this->renderErrorPage('Link not found');
        }

        // Validate link ownership
        if ((int) $link['user_id'] !== $user['id']) {
            return $this->renderErrorPage('This link does not belong to you');
        }

        // Check if already completed
        if ($link['completed'] === 'true') {
            return $this->renderErrorPage('This link has already been completed');
        }

        $provider = $link['provider'];
        $settings = LinksHelper::getProviderSettings($provider);
        if (!$settings) {
            return $this->renderErrorPage('Provider settings not found');
        }

        // Get min_time_to_complete - handle empty strings and null values
        $minTimeSetting = $settings['min_time_to_complete'] ?? '';
        // Trim whitespace and check if it's a valid number
        $minTimeSetting = is_string($minTimeSetting) ? trim($minTimeSetting) : $minTimeSetting;
        $minToComplete = !empty($minTimeSetting) && is_numeric($minTimeSetting) && (int) $minTimeSetting > 0
            ? (int) $minTimeSetting
            : 60;

        // Get coins_per_link - handle empty strings and null values
        $coinsSetting = $settings['coins_per_link'] ?? '';
        // Trim whitespace and check if it's a valid number
        $coinsSetting = is_string($coinsSetting) ? trim($coinsSetting) : $coinsSetting;
        $coinsPerLink = !empty($coinsSetting) && is_numeric($coinsSetting) && (int) $coinsSetting > 0
            ? (int) $coinsSetting
            : 100;

        // Check minimum time to complete
        // Use DateTime for proper timezone handling
        try {
            $createdAt = new \DateTime($link['created_at']);
            $now = new \DateTime();
            $timeTaken = $now->getTimestamp() - $createdAt->getTimestamp();
        } catch (\Exception $e) {
            // Fallback to strtotime if DateTime fails
            $createdAt = strtotime($link['created_at']);
            $now = time();
            $timeTaken = $now - $createdAt;
        }

        // If time is negative, it means the link was created in the future (timestamp issue)
        // In this case, we should allow it but log the issue
        if ($timeTaken < 0) {
            $app = App::getInstance(true);
            $app->getLogger()->warning("Link {$code} has negative time taken ({$timeTaken}s) - timestamp issue detected. Allowing completion.");
            // Don't fail for timestamp issues - just log it and allow completion
        } elseif ($timeTaken < $minToComplete) {
            // Delete the link if completed too fast
            Link::delete($linkId);

            Activity::createActivity([
                'user_uuid' => $user['uuid'] ?? null,
                'name' => 'link_completed_too_fast',
                'context' => "Link {$code} completed too fast (took {$timeTaken}s, minimum {$minToComplete}s, setting value: " . ($settings['min_time_to_complete'] ?? 'not set') . ', provider: ' . $provider . ')',
                'ip_address' => CloudFlareRealIP::getRealIP(),
            ]);

            return $this->renderTooFastPage($minToComplete);
        }

        // Mark as completed
        if (!Link::markAsCompleted($linkId)) {
            return $this->renderErrorPage('Failed to mark link as completed');
        }

        // Add credits
        $coinsToAdd = (int) $coinsPerLink;
        if (!CreditsHelper::addUserCredits($user['id'], $coinsToAdd)) {
            // Log error but don't fail the request - link is already marked as completed
            App::getInstance(true)->getLogger()->error("Failed to add credits for user {$user['id']} link {$linkId}");
        }

        Activity::createActivity([
            'user_uuid' => $user['uuid'] ?? null,
            'name' => 'link_redeemed',
            'context' => "Redeemed {$provider} link: {$code} for {$coinsPerLink} credits",
            'ip_address' => CloudFlareRealIP::getRealIP(),
        ]);

        return $this->renderSuccessPage($coinsPerLink);
    }

    /**
     * Get user's link history.
     */
    #[OA\Get(
        path: '/api/user/billinglinks/history',
        summary: 'Get link history',
        description: 'Get the current user\'s link completion history',
        tags: ['User - Billing Links'],
        responses: [
            new OA\Response(response: 200, description: 'History retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function getHistory(Request $request): Response
    {
        $user = $request->attributes->get('user') ?? $request->get('user');
        if (!$user || !isset($user['id'])) {
            return ApiResponse::error('User not authenticated', 'UNAUTHORIZED', 401);
        }

        $links = Link::getAllByUser($user['id'], 50);

        return ApiResponse::success([
            'links' => $links,
            'total' => count($links),
        ], 'History retrieved successfully', 200);
    }

    /**
     * Get available providers and status.
     */
    #[OA\Get(
        path: '/api/user/billinglinks/providers',
        summary: 'Get available providers',
        description: 'Get list of available link providers and their settings',
        tags: ['User - Billing Links'],
        responses: [
            new OA\Response(response: 200, description: 'Providers retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function getProviders(Request $request): Response
    {
        $user = $request->attributes->get('user') ?? $request->get('user');
        if (!$user || !isset($user['id'])) {
            return ApiResponse::error('User not authenticated', 'UNAUTHORIZED', 401);
        }

        if (!LinksHelper::isEnabled()) {
            return ApiResponse::error('Links4Rewards is currently disabled', 'L4R_DISABLED', 403);
        }

        $providers = ['linkvertise', 'shareus', 'linkpays', 'gyanilinks'];
        $availableProviders = [];

        foreach ($providers as $provider) {
            if (LinksHelper::isProviderEnabled($provider)) {
                $settings = LinksHelper::getProviderSettings($provider);
                if ($settings) {
                    $availableProviders[] = [
                        'name' => $provider,
                        'enabled' => true,
                        'coins_per_link' => (int) ($settings['coins_per_link'] ?? 100),
                        'daily_limit' => (int) ($settings['daily_limit'] ?? 5),
                    ];
                }
            }
        }

        return ApiResponse::success([
            'providers' => $availableProviders,
            'l4r_enabled' => true,
        ], 'Providers retrieved successfully', 200);
    }

    /**
     * Get shortened link from provider service.
     */
    private function getShortenedLink(string $provider, string $url, array $settings): string
    {
        $apiKey = $settings['api_key'] ?? '';
        if (empty($apiKey)) {
            throw new \Exception('API key not configured for ' . $provider);
        }

        switch ($provider) {
            case 'shareus':
                $service = new ShareUS($apiKey);

                return $service->getLink($url);

            case 'linkpays':
                $service = new LinkPays($apiKey);

                return $service->getLink($url);

            case 'gyanilinks':
                $service = new GyaniLinks($apiKey);

                return $service->getLink($url);

            default:
                throw new \Exception('Unknown provider: ' . $provider);
        }
    }

    /**
     * Render cooldown page.
     */
    private function renderCooldownPage(int $waitMinutes): Response
    {
        header('Content-Type: text/html');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cooldown</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #111827;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: system-ui, -apple-system, sans-serif;
                }
                .container {
                    text-align: center;
                }
                h1 {
                    color: #ffffff;
                    font-size: 1.875rem;
                    font-weight: bold;
                    margin-bottom: 1.5rem;
                }
                p {
                    color: #9CA3AF;
                    margin-bottom: 2rem;
                }
                .button {
                    background-color: #4F46E5;
                    color: #ffffff;
                    padding: 0.75rem 1.5rem;
                    border-radius: 0.5rem;
                    text-decoration: none;
                    font-weight: bold;
                    transition: background-color 0.2s;
                    display: inline-block;
                }
                .button:hover {
                    background-color: #4338CA;
                }
                .button.btn-back {
                    background-color: #6B7280;
                }
                .button.btn-back:hover {
                    background-color: #4B5563;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Please wait</h1>
                <p>You need to wait <?php echo htmlspecialchars($waitMinutes); ?> minutes before creating another link</p>
                <a href="/earn/links" class="button btn-back" onclick="window.close(); return false;">Go back</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Render daily limit page.
     */
    private function renderDailyLimitPage(int $dayLimit): Response
    {
        header('Content-Type: text/html');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Daily Limit Reached</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #111827;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: system-ui, -apple-system, sans-serif;
                }
                .container {
                    text-align: center;
                }
                h1 {
                    color: #ffffff;
                    font-size: 1.875rem;
                    font-weight: bold;
                    margin-bottom: 1.5rem;
                }
                p {
                    color: #9CA3AF;
                    margin-bottom: 2rem;
                }
                .button {
                    background-color: #4F46E5;
                    color: #ffffff;
                    padding: 0.75rem 1.5rem;
                    border-radius: 0.5rem;
                    text-decoration: none;
                    font-weight: bold;
                    transition: background-color 0.2s;
                    display: inline-block;
                }
                .button:hover {
                    background-color: #4338CA;
                }
                .button.btn-back {
                    background-color: #6B7280;
                }
                .button.btn-back:hover {
                    background-color: #4B5563;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Daily Limit Reached</h1>
                <p>You have reached your daily limit of <?php echo htmlspecialchars($dayLimit); ?> links. Please try again tomorrow.</p>
                <a href="/earn/links" class="button btn-back" onclick="window.close(); return false;">Go back</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Render error page.
     */
    private function renderErrorPage(string $message): Response
    {
        header('Content-Type: text/html');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Error</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #111827;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: system-ui, -apple-system, sans-serif;
                }
                .container {
                    text-align: center;
                }
                h1 {
                    color: #ffffff;
                    font-size: 1.875rem;
                    font-weight: bold;
                    margin-bottom: 1.5rem;
                }
                p {
                    color: #9CA3AF;
                    margin-bottom: 2rem;
                }
                .button {
                    background-color: #4F46E5;
                    color: #ffffff;
                    padding: 0.75rem 1.5rem;
                    border-radius: 0.5rem;
                    text-decoration: none;
                    font-weight: bold;
                    transition: background-color 0.2s;
                    display: inline-block;
                }
                .button:hover {
                    background-color: #4338CA;
                }
                .button.btn-back {
                    background-color: #6B7280;
                }
                .button.btn-back:hover {
                    background-color: #4B5563;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Error</h1>
                <p><?php echo htmlspecialchars($message); ?></p>
                <a href="/earn/links" class="button btn-back" onclick="window.close(); return false;">Go back</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Render too fast page.
     */
    private function renderTooFastPage(int $minToComplete): Response
    {
        header('Content-Type: text/html');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Too Fast</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #111827;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: system-ui, -apple-system, sans-serif;
                }
                .container {
                    text-align: center;
                }
                h1 {
                    color: #ffffff;
                    font-size: 1.875rem;
                    font-weight: bold;
                    margin-bottom: 1.5rem;
                }
                p {
                    color: #9CA3AF;
                    margin-bottom: 2rem;
                }
                .button {
                    background-color: #4F46E5;
                    color: #ffffff;
                    padding: 0.75rem 1.5rem;
                    border-radius: 0.5rem;
                    text-decoration: none;
                    font-weight: bold;
                    transition: background-color 0.2s;
                    display: inline-block;
                }
                .button:hover {
                    background-color: #4338CA;
                }
                .button.btn-back {
                    background-color: #6B7280;
                }
                .button.btn-back:hover {
                    background-color: #4B5563;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Too Fast!</h1>
                <p>You completed the link too quickly. Please take at least <?php echo htmlspecialchars($minToComplete); ?> seconds to complete the link next time.</p>
                <a href="/earn/links" class="button btn-back" onclick="window.close(); return false;">Go back</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Render success page.
     */
    private function renderSuccessPage(int $coinsPerLink): Response
    {
        header('Content-Type: text/html');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Success</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #111827;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: system-ui, -apple-system, sans-serif;
                }
                .container {
                    text-align: center;
                }
                h1 {
                    color: #ffffff;
                    font-size: 1.875rem;
                    font-weight: bold;
                    margin-bottom: 1.5rem;
                }
                p {
                    color: #9CA3AF;
                    margin-bottom: 2rem;
                }
                .button {
                    background-color: #4F46E5;
                    color: #ffffff;
                    padding: 0.75rem 1.5rem;
                    border-radius: 0.5rem;
                    text-decoration: none;
                    font-weight: bold;
                    transition: background-color 0.2s;
                    display: inline-block;
                }
                .button:hover {
                    background-color: #4338CA;
                }
                .button.btn-back {
                    background-color: #6B7280;
                }
                .button.btn-back:hover {
                    background-color: #4B5563;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Success!</h1>
                <p>You have successfully completed the link and earned <?php echo htmlspecialchars($coinsPerLink); ?> credits!</p>
                <a href="/earn/links" class="button btn-back" onclick="window.close(); return false;">Go back</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Render Linkvertise page with script.
     */
    private function renderLinkvertisePage(string $linkUuid, string $userId): Response
    {
        header('Content-Type: text/html');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://publisher.linkvertise.com; style-src 'self' 'unsafe-inline';");
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Continue to Linkvertise</title>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    background-color: #111827;
                    height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-family: system-ui, -apple-system, sans-serif;
                }
                .container {
                    text-align: center;
                }
                h1 {
                    color: #ffffff;
                    font-size: 1.875rem;
                    font-weight: bold;
                    margin-bottom: 1.5rem;
                }
                p {
                    color: #9CA3AF;
                    margin-bottom: 2rem;
                }
                .button {
                    background-color: #4F46E5;
                    color: #ffffff;
                    padding: 0.75rem 1.5rem;
                    border-radius: 0.5rem;
                    text-decoration: none;
                    font-weight: bold;
                    transition: background-color 0.2s;
                    display: inline-block;
                    margin: 0.5rem;
                }
                .button:hover {
                    background-color: #4338CA;
                }
                .button.btn-back {
                    background-color: #6B7280;
                }
                .button.btn-back:hover {
                    background-color: #4B5563;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Continue to Linkvertise</h1>
                <p>Click the button below to continue to Linkvertise and earn rewards</p>
                <a href="/api/user/billinglinks/earn/<?php echo htmlspecialchars($linkUuid); ?>" class="button">
                    Continue to Linkvertise
                </a>
                <a href="/dashboard" class="button btn-back" onclick="window.close(); return false;">
                    Go back
                </a>
                <script src="https://publisher.linkvertise.com/cdn/linkvertise.js"></script>
                <script>linkvertise(<?php echo htmlspecialchars($userId); ?>, { whitelist: [], blacklist: [] });</script>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
