<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226090253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create workflow_task_links table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workflow_task_links (id VARCHAR(36) NOT NULL, process_instance_id VARCHAR(36) NOT NULL, token_id VARCHAR(36) NOT NULL, task_id VARCHAR(36) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_wf_task_link_task ON workflow_task_links (task_id)');
        $this->addSql('CREATE INDEX idx_wf_task_link_process ON workflow_task_links (process_instance_id, token_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE workflow_task_links');
    }
}
