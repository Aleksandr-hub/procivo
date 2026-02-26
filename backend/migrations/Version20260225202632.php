<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225202632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task_manager_boards and task_manager_board_columns tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_manager_boards (id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_board_organization ON task_manager_boards (organization_id)');

        $this->addSql('CREATE TABLE task_manager_board_columns (id VARCHAR(36) NOT NULL, board_id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, position INT NOT NULL, status_mapping VARCHAR(50) DEFAULT NULL, wip_limit INT DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_column_board ON task_manager_board_columns (board_id)');
        $this->addSql('CREATE INDEX idx_column_position ON task_manager_board_columns (board_id, position)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task_manager_board_columns');
        $this->addSql('DROP TABLE task_manager_boards');
    }
}
