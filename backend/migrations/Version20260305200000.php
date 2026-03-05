<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add board_type, process_definition_id to task_manager_boards and node_id to task_manager_board_columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE task_manager_boards ADD COLUMN board_type VARCHAR(20) NOT NULL DEFAULT 'task_board'");
        $this->addSql('ALTER TABLE task_manager_boards ADD COLUMN process_definition_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE task_manager_board_columns ADD COLUMN node_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_board_type ON task_manager_boards (board_type)');
        $this->addSql('CREATE INDEX idx_board_process_def ON task_manager_boards (process_definition_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_board_process_def');
        $this->addSql('DROP INDEX IF EXISTS idx_board_type');
        $this->addSql('ALTER TABLE task_manager_board_columns DROP COLUMN IF EXISTS node_id');
        $this->addSql('ALTER TABLE task_manager_boards DROP COLUMN IF EXISTS process_definition_id');
        $this->addSql('ALTER TABLE task_manager_boards DROP COLUMN IF EXISTS board_type');
    }
}
