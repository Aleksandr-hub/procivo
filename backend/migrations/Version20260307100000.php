<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at column for soft delete to users, organizations, tasks, and process definitions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE identity_users ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE organization_organizations ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE task_manager_tasks ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE workflow_process_definitions ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        $this->addSql('CREATE INDEX idx_identity_users_deleted_at ON identity_users (deleted_at)');
        $this->addSql('CREATE INDEX idx_organizations_deleted_at ON organization_organizations (deleted_at)');
        $this->addSql('CREATE INDEX idx_tasks_deleted_at ON task_manager_tasks (deleted_at)');
        $this->addSql('CREATE INDEX idx_process_definitions_deleted_at ON workflow_process_definitions (deleted_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_identity_users_deleted_at');
        $this->addSql('DROP INDEX idx_organizations_deleted_at');
        $this->addSql('DROP INDEX idx_tasks_deleted_at');
        $this->addSql('DROP INDEX idx_process_definitions_deleted_at');

        $this->addSql('ALTER TABLE identity_users DROP COLUMN deleted_at');
        $this->addSql('ALTER TABLE organization_organizations DROP COLUMN deleted_at');
        $this->addSql('ALTER TABLE task_manager_tasks DROP COLUMN deleted_at');
        $this->addSql('ALTER TABLE workflow_process_definitions DROP COLUMN deleted_at');
    }
}
