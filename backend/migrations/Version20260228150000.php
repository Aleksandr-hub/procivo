<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sequence_number to task_manager_tasks for human-readable task IDs (TASK-001)';
    }

    public function up(Schema $schema): void
    {
        // Add column with default 0
        $this->addSql('ALTER TABLE task_manager_tasks ADD COLUMN sequence_number INTEGER NOT NULL DEFAULT 0');

        // Backfill existing tasks: assign sequential numbers per organization ordered by created_at
        $this->addSql('
            UPDATE task_manager_tasks t
            SET sequence_number = sub.rn
            FROM (
                SELECT id, ROW_NUMBER() OVER (PARTITION BY organization_id ORDER BY created_at ASC) AS rn
                FROM task_manager_tasks
            ) sub
            WHERE t.id = sub.id
        ');

        // Remove default
        $this->addSql('ALTER TABLE task_manager_tasks ALTER COLUMN sequence_number DROP DEFAULT');

        // Add unique constraint
        $this->addSql('CREATE UNIQUE INDEX uniq_task_org_sequence ON task_manager_tasks (organization_id, sequence_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_task_org_sequence');
        $this->addSql('ALTER TABLE task_manager_tasks DROP COLUMN sequence_number');
    }
}
