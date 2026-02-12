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
