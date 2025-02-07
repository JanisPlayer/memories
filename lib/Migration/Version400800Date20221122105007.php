<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Varun Patil <radialapps@gmail.com>
 * @author Varun Patil <radialapps@gmail.com>
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Memories\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version400800Date20221122105007 extends SimpleMigrationStep
{
    /**
     * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     */
    public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void
    {
    }

    /**
     * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     */
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $table = $schema->getTable('memories');

        if (!$table->hasColumn('liveid')) {
            $table->addColumn('liveid', 'string', [
                'notnull' => false,
                'length' => 256,
                'default' => '',
            ]);
        }

        // Live photos table
        if (!$schema->hasTable('memories_livephoto')) {
            $table = $schema->createTable('memories_livephoto');

            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);

            $table->addColumn('liveid', 'string', [
                'notnull' => true,
                'length' => 256,
            ]);

            $table->addColumn('fileid', Types::BIGINT, [
                'notnull' => true,
                'length' => 20,
            ]);

            $table->addColumn('mtime', Types::INTEGER, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['liveid'], 'memories_lp_liveid_index');
            $table->addUniqueIndex(['fileid'], 'memories_lp_fileid_index');
        }

        return $schema;
    }

    /**
     * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     */
    public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void
    {
    }
}
