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

namespace App\Addons\billinglinks\Cron;

use App\App;
use App\Cron\Cron;
use App\Chat\Database;
use App\Cron\TimeTask;
use App\Chat\TimedTask;
use App\Cli\Utils\MinecraftColorCodeSupport;

/**
 * LinkPurgeCron - Cron task for purging old deleted/completed links.
 *
 * This cron job runs daily and permanently deletes links that:
 * - Are marked as deleted (deleted = 'true')
 * - Were deleted more than 7 days ago
 * - OR are completed and older than 30 days
 */
class LinkPurgeCron implements TimeTask
{
    private const TABLE = 'featherpanel_billinglinks_links';
    private const DELETE_AFTER_DAYS = 7; // Delete deleted links after 7 days
    private const PURGE_COMPLETED_AFTER_DAYS = 30; // Purge completed links after 30 days

    /**
     * Entry point for the cron job.
     */
    public function run(): void
    {
        $cron = new Cron('billinglinks-link-purge', '1D');
        try {
            $cron->runIfDue(function () {
                $this->purgeLinks();
                TimedTask::markRun('billinglinks-link-purge', true, 'Link purge completed');
            });
        } catch (\Exception $e) {
            $app = App::getInstance(false, true);
            $app->getLogger()->error('Failed to run billinglinks link purge cron job: ' . $e->getMessage());
            MinecraftColorCodeSupport::sendOutputWithNewLine('&cFailed to run billinglinks link purge: ' . $e->getMessage());
            TimedTask::markRun('billinglinks-link-purge', false, $e->getMessage());
        }
    }

    /**
     * Purge old deleted and completed links.
     */
    private function purgeLinks(): void
    {
        $pdo = Database::getPdoConnection();
        $deletedCount = 0;
        $completedCount = 0;

        try {
            // Delete old deleted links (deleted = 'true' and deleted more than DELETE_AFTER_DAYS days ago)
            $cutoffDate = date('Y-m-d H:i:s', strtotime('-' . self::DELETE_AFTER_DAYS . ' days'));
            $stmt = $pdo->prepare(
                'DELETE FROM ' . self::TABLE . " 
                WHERE deleted = 'true' 
                AND updated_at < :cutoff_date"
            );
            $stmt->execute(['cutoff_date' => $cutoffDate]);
            $deletedCount = $stmt->rowCount();

            if ($deletedCount > 0) {
                $msg = "Purged {$deletedCount} deleted link(s) older than " . self::DELETE_AFTER_DAYS . ' days';
                App::getInstance(true)->getLogger()->info($msg);
                MinecraftColorCodeSupport::sendOutputWithNewLine('&a' . $msg);
            }

            // Delete old completed links (completed = 'true' and older than PURGE_COMPLETED_AFTER_DAYS days)
            $completedCutoffDate = date('Y-m-d H:i:s', strtotime('-' . self::PURGE_COMPLETED_AFTER_DAYS . ' days'));
            $stmt = $pdo->prepare(
                'DELETE FROM ' . self::TABLE . " 
                WHERE completed = 'true' 
                AND deleted = 'false'
                AND created_at < :cutoff_date"
            );
            $stmt->execute(['cutoff_date' => $completedCutoffDate]);
            $completedCount = $stmt->rowCount();

            if ($completedCount > 0) {
                $msg = "Purged {$completedCount} completed link(s) older than " . self::PURGE_COMPLETED_AFTER_DAYS . ' days';
                App::getInstance(true)->getLogger()->info($msg);
                MinecraftColorCodeSupport::sendOutputWithNewLine('&a' . $msg);
            }

            $totalPurged = $deletedCount + $completedCount;
            if ($totalPurged === 0) {
                MinecraftColorCodeSupport::sendOutputWithNewLine('&7No old links to purge');
            } else {
                $summaryMsg = "Link purge completed: {$totalPurged} total link(s) purged ({$deletedCount} deleted, {$completedCount} completed)";
                App::getInstance(true)->getLogger()->info($summaryMsg);
                MinecraftColorCodeSupport::sendOutputWithNewLine('&a' . $summaryMsg);
            }
        } catch (\Exception $e) {
            $errorMsg = 'Failed to purge links: ' . $e->getMessage();
            App::getInstance(true)->getLogger()->error($errorMsg);
            MinecraftColorCodeSupport::sendOutputWithNewLine('&c' . $errorMsg);
            throw $e;
        }
    }
}
