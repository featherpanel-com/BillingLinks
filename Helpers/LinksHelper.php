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

namespace App\Addons\billinglinks\Helpers;

use App\Plugins\PluginSettings;

/**
 * Helper class for managing Links4Rewards (L4R) settings.
 */
class LinksHelper
{
    private const PLUGIN_IDENTIFIER = 'billinglinks';

    /**
     * Check if L4R is enabled.
     */
    public static function isEnabled(): bool
    {
        return PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_enabled') === 'true';
    }

    /**
     * Get all L4R settings.
     *
     * @return array Array of settings
     */
    public static function getSettings(): array
    {
        return [
            'l4r_enabled' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_enabled') ?? 'false',

            // Linkvertise
            'l4r_linkvertise_enabled' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkvertise_enabled') ?? 'false',
            'l4r_linkvertise_user_id' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkvertise_user_id') ?? '',
            'l4r_linkvertise_coins_per_link' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkvertise_coins_per_link') ?? '100',
            'l4r_linkvertise_daily_limit' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkvertise_daily_limit') ?? '5',
            'l4r_linkvertise_min_time_to_complete' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkvertise_min_time_to_complete') ?? '60',
            'l4r_linkvertise_time_to_expire' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkvertise_time_to_expire') ?? '3600',
            'l4r_linkvertise_cooldown_time' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkvertise_cooldown_time') ?? '3600',

            // ShareUs
            'l4r_shareus_enabled' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_shareus_enabled') ?? 'false',
            'l4r_shareus_api_key' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_shareus_api_key') ?? '',
            'l4r_shareus_coins_per_link' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_shareus_coins_per_link') ?? '100',
            'l4r_shareus_daily_limit' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_shareus_daily_limit') ?? '5',
            'l4r_shareus_min_time_to_complete' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_shareus_min_time_to_complete') ?? '60',
            'l4r_shareus_time_to_expire' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_shareus_time_to_expire') ?? '3600',
            'l4r_shareus_cooldown_time' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_shareus_cooldown_time') ?? '3600',

            // LinkPays
            'l4r_linkpays_enabled' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkpays_enabled') ?? 'false',
            'l4r_linkpays_api_key' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkpays_api_key') ?? '',
            'l4r_linkpays_coins_per_link' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkpays_coins_per_link') ?? '100',
            'l4r_linkpays_daily_limit' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkpays_daily_limit') ?? '5',
            'l4r_linkpays_min_time_to_complete' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkpays_min_time_to_complete') ?? '60',
            'l4r_linkpays_time_to_expire' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkpays_time_to_expire') ?? '3600',
            'l4r_linkpays_cooldown_time' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_linkpays_cooldown_time') ?? '3600',

            // GyaniLinks
            'l4r_gyanilinks_enabled' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_gyanilinks_enabled') ?? 'false',
            'l4r_gyanilinks_api_key' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_gyanilinks_api_key') ?? '',
            'l4r_gyanilinks_coins_per_link' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_gyanilinks_coins_per_link') ?? '100',
            'l4r_gyanilinks_daily_limit' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_gyanilinks_daily_limit') ?? '5',
            'l4r_gyanilinks_min_time_to_complete' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_gyanilinks_min_time_to_complete') ?? '60',
            'l4r_gyanilinks_time_to_expire' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_gyanilinks_time_to_expire') ?? '3600',
            'l4r_gyanilinks_cooldown_time' => PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_gyanilinks_cooldown_time') ?? '3600',
        ];
    }

    /**
     * Get settings for a specific provider.
     *
     * @param string $provider The provider name (linkvertise, shareus, linkpays, gyanilinks)
     *
     * @return array|null Provider settings or null if invalid provider
     */
    public static function getProviderSettings(string $provider): ?array
    {
        $settings = self::getSettings();
        $provider = strtolower($provider);

        $providerSettings = [];
        foreach ($settings as $key => $value) {
            if (strpos($key, 'l4r_' . $provider . '_') === 0) {
                $providerSettings[str_replace('l4r_' . $provider . '_', '', $key)] = $value;
            }
        }

        return !empty($providerSettings) ? $providerSettings : null;
    }

    /**
     * Check if a provider is enabled.
     *
     * @param string $provider The provider name
     *
     * @return bool True if enabled
     */
    public static function isProviderEnabled(string $provider): bool
    {
        $provider = strtolower($provider);

        return PluginSettings::getSetting(self::PLUGIN_IDENTIFIER, 'l4r_' . $provider . '_enabled') === 'true';
    }
}
