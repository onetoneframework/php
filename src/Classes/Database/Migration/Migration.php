<?php
declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\Migration;

use Clover\Classes\Database\Driver\PHPDataObject;

/**
 * Base migration class.
 *
 * Create migration files under:
 *   App/Database/Migrations
 *
 * Example:
 *   final class 202604080001_CreateUsersTable extends Migration {
 *       public function up(PHPDataObject $db): void {
 *           $db->executeQuery('CREATE TABLE ...');
 *       }
 *   }
 */
abstract class Migration
{
    /**
     * Execute the migration.
     */
    abstract public function up(PHPDataObject $db): void;

    /**
     * Rollback (optional).
     */
    public function down(PHPDataObject $db): void
    {
        // no-op by default
    }
}

