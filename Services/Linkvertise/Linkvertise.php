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

namespace App\Addons\billinglinks\Services\Linkvertise;

/**
 * Build a Linkvertise dynamic short URL for a destination (the earn callback).
 *
 * The official publisher script rewrites page links in the browser. That is
 * unreliable here: the "Continue" button already pointed at the earn URL, so
 * if the script was blocked, failed (crypto / DOMContentLoaded), or never
 * rewrote the href, users hit the completion endpoint immediately and saw
 * "You completed the link too quickly" without ever opening Linkvertise.
 *
 * We generate the same dynamic URL format the publisher script has used
 * (https://link-to.net/{userId}/{random}/dynamic/?r={base64(url)}) and
 * redirect the user there server-side, like the other L4R providers.
 */
class Linkvertise
{
    public const BASE_URL = 'https://link-to.net';

    /**
     * Build a Linkvertise URL that returns to $targetUrl after completion.
     *
     * @param string $userId Publisher / user ID from the Linkvertise dashboard
     * @param string $targetUrl Absolute http(s) callback URL
     *
     * @throws \InvalidArgumentException If the user ID or target URL is invalid
     *
     * @return string The Linkvertise short URL
     */
    public function getLink(string $userId, string $targetUrl): string
    {
        $userId = trim($userId);
        if ($userId === '' || !preg_match('/^[0-9]+$/', $userId)) {
            throw new \InvalidArgumentException('Linkvertise user ID is not configured or is invalid');
        }

        $targetUrl = trim($targetUrl);
        if (!preg_match('#^https?://[^\s]+$#i', $targetUrl)) {
            throw new \InvalidArgumentException('Linkvertise target URL must be an absolute http(s) URL');
        }

        $random = $this->randomPathSegment();
        $payload = base64_encode($targetUrl);

        return self::BASE_URL . '/' . $userId . '/' . $random . '/dynamic/?r=' . $payload;
    }

    /**
     * Same range as the official publisher script: Math.random() * 1000.
     */
    protected function randomPathSegment(): string
    {
        return (string) (mt_rand(0, 999999) / 1000);
    }
}
