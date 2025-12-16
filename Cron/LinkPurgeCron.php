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
