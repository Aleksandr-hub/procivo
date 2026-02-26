<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create workflow_process_instances_view table (Read Model)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workflow_process_instances_view (
            id VARCHAR(36) NOT NULL,
            definition_id VARCHAR(36) NOT NULL,
            definition_name VARCHAR(255) NOT NULL DEFAULT \'\',
            version_id VARCHAR(36) NOT NULL,
            organization_id VARCHAR(36) NOT NULL,
            status VARCHAR(20) NOT NULL,
            started_by VARCHAR(36) NOT NULL,
            variables JSONB NOT NULL DEFAULT \'{}\'::jsonb,
            tokens JSONB NOT NULL DEFAULT \'{}\'::jsonb,
            started_at TIMESTAMP(6) WITHOUT TIME ZONE NOT NULL,
            completed_at TIMESTAMP(6) WITHOUT TIME ZONE DEFAULT NULL,
            cancelled_at TIMESTAMP(6) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE INDEX idx_wf_view_org ON workflow_process_instances_view (organization_id)');
        $this->addSql('CREATE INDEX idx_wf_view_org_status ON workflow_process_instances_view (organization_id, status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE workflow_process_instances_view');
    }
}
