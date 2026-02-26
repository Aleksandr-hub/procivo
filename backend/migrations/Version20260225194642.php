<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225194642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task_manager_tasks table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_manager_tasks (id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, priority VARCHAR(20) NOT NULL, due_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, estimated_hours DOUBLE PRECISION DEFAULT NULL, assignee_id VARCHAR(36) DEFAULT NULL, creator_id VARCHAR(36) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_task_organization ON task_manager_tasks (organization_id)');
        $this->addSql('CREATE INDEX idx_task_status ON task_manager_tasks (status)');
        $this->addSql('CREATE INDEX idx_task_assignee ON task_manager_tasks (assignee_id)');
        $this->addSql('CREATE INDEX idx_task_due_date ON task_manager_tasks (due_date)');
        $this->addSql('CREATE INDEX idx_task_creator ON task_manager_tasks (creator_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task_manager_tasks');
    }
}
