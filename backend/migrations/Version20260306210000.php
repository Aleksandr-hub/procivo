<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create workflow_process_definition_access table for per-definition ACL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workflow_process_definition_access (
            id VARCHAR(36) NOT NULL,
            process_definition_id VARCHAR(36) NOT NULL,
            organization_id VARCHAR(36) NOT NULL,
            department_id VARCHAR(36) DEFAULT NULL,
            role_id VARCHAR(36) DEFAULT NULL,
            access_type VARCHAR(10) NOT NULL,
            created_at TIMESTAMP(6) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE INDEX idx_wf_def_access_def_type ON workflow_process_definition_access (process_definition_id, access_type)');
        $this->addSql('CREATE INDEX idx_wf_def_access_org ON workflow_process_definition_access (organization_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_wf_def_access_entry ON workflow_process_definition_access (process_definition_id, department_id, role_id, access_type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uq_wf_def_access_entry');
        $this->addSql('DROP INDEX IF EXISTS idx_wf_def_access_org');
        $this->addSql('DROP INDEX IF EXISTS idx_wf_def_access_def_type');
        $this->addSql('DROP TABLE IF EXISTS workflow_process_definition_access');
    }
}
