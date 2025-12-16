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

namespace App\Addons\billinglinks\Chat;

use App\App;
use App\Chat\Database;

/**
 * Link chat model for managing reward links.
 */
class Link
{
    private static string $table = 'featherpanel_billinglinks_links';

    /**
     * Create a new link.
     *
     * @param string $code The unique code/UUID for the link
     * @param int $userId The user ID who created the link
     * @param string $provider The link provider (linkvertise, shareus, linkpays, gyanilinks)
     *
     * @return int The ID of the created link, or 0 on failure
     */
    public static function create(string $code, int $userId, string $provider): int
    {
        if (empty($code) || $userId <= 0 || empty($provider)) {
            return 0;
        }

        // Validate code format (UUID)
        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $code)) {
            return 0;
        }

        $pdo = Database::getPdoConnection();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO ' . self::$table . ' (code, user_id, provider) 
                VALUES (:code, :user_id, :provider)'
            );
            $stmt->execute([
                'code' => $code,
                'user_id' => $userId,
                'provider' => $provider,
            ]);

            return (int) $pdo->lastInsertId();
        } catch (\PDOException $e) {
            App::getInstance(true)->getLogger()->error('Failed to create link: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * Get all links by user.
     *
     * @param int $userId The user ID
     * @param int $limit The limit of links to get
     *
     * @return array Array of links
     */
    public static function getAllByUser(int $userId, int $limit = 150): array
    {
        if ($userId <= 0) {
            return [];
        }

        if ($limit < 1 || $limit > 150) {
            $limit = 150;
        }

        $pdo = Database::getPdoConnection();

        try {
            $stmt = $pdo->prepare(
                'SELECT * FROM ' . self::$table . ' 
                WHERE deleted = \'false\' AND locked = \'false\' AND user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit'
            );
            $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            App::getInstance(true)->getLogger()->error('Failed to get links by user: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Get a link by its code.
     *
     * @param string $code The link code
     *
     * @return array|null The link data or null if not found
     */
    public static function getByCode(string $code): ?array
    {
        if (empty($code)) {
            return null;
        }

        $pdo = Database::getPdoConnection();

        try {
            $stmt = $pdo->prepare(
                'SELECT * FROM ' . self::$table . ' 
                WHERE code = :code AND deleted = \'false\' AND locked = \'false\' 
                LIMIT 1'
            );
            $stmt->execute(['code' => $code]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result !== false ? $result : null;
        } catch (\PDOException $e) {
            App::getInstance(true)->getLogger()->error('Failed to get link by code: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Convert a code to an ID.
     *
     * @param string $code The link code
     *
     * @return int The link ID, or 0 if not found
     */
    public static function convertCodeToId(string $code): int
    {
        if (empty($code)) {
            return 0;
        }

        $pdo = Database::getPdoConnection();

        try {
            $stmt = $pdo->prepare(
                'SELECT id FROM ' . self::$table . ' 
                WHERE code = :code AND deleted = \'false\' AND locked = \'false\' 
                LIMIT 1'
            );
            $stmt->execute(['code' => $code]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result ? (int) $result['id'] : 0;
        } catch (\PDOException $e) {
            App::getInstance(true)->getLogger()->error('Failed to convert code to id: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * Get a link by its ID.
     *
     * @param int $id The link ID
     *
     * @return array|null The link data or null if not found
     */
    public static function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $pdo = Database::getPdoConnection();

        try {
            $stmt = $pdo->prepare(
                'SELECT * FROM ' . self::$table . ' 
                WHERE id = :id AND deleted = \'false\' AND locked = \'false\' 
                LIMIT 1'
            );
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result !== false ? $result : null;
        } catch (\PDOException $e) {
            App::getInstance(true)->getLogger()->error('Failed to get link by id: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Mark a link as completed.
     *
     * @param int $id The link ID
     *
     * @return bool True on success, false on failure
     */
    public static function markAsCompleted(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $pdo = Database::getPdoConnection();

        try {
            $stmt = $pdo->prepare(
                'UPDATE ' . self::$table . ' 
                SET completed = \'true\' 
                WHERE id = :id AND deleted = \'false\' AND locked = \'false\''
            );
            $stmt->execute(['id' => $id]);

            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            App::getInstance(true)->getLogger()->error('Failed to mark link as completed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Delete a link (soft delete).
     *
     * @param int $id The link ID
     *
     * @return bool True on success, false on failure
     */
    public static function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $pdo = Database::getPdoConnection();

        try {
            $stmt = $pdo->prepare(
                'UPDATE ' . self::$table . ' 
                SET deleted = \'true\' 
                WHERE id = :id'
            );
            $stmt->execute(['id' => $id]);

            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            App::getInstance(true)->getLogger()->error('Failed to delete link: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get all links with pagination.
     *
     * @param int $limit The limit
     * @param int $offset The offset
     *
     * @return array Array of links
     */
    public static function getAll(int $limit = 50, int $offset = 0): array
    {
        $pdo = Database::getPdoConnection();

        try {
            $stmt = $pdo->prepare(
                'SELECT * FROM ' . self::$table . ' 
                WHERE deleted = \'false\' 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset'
            );
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            App::getInstance(true)->getLogger()->error('Failed to get all links: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Get total count of links.
     *
     * @return int The count
     */
    public static function getCount(): int
    {
        $pdo = Database::getPdoConnection();

        try {
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM ' . self::$table . ' WHERE deleted = \'false\'');
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result ? (int) $result['count'] : 0;
        } catch (\PDOException $e) {
            App::getInstance(true)->getLogger()->error('Failed to get link count: ' . $e->getMessage());

            return 0;
        }
    }
}
