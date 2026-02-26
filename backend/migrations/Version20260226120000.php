<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create workflow_process_events table (Event Store for ProcessInstance)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workflow_process_events (
            id VARCHAR(36) NOT NULL,
            aggregate_id VARCHAR(36) NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            payload JSONB NOT NULL,
            version INT NOT NULL,
            occurred_at TIMESTAMP(6) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE INDEX idx_wf_events_aggregate ON workflow_process_events (aggregate_id, version)');
        $this->addSql('CREATE UNIQUE INDEX uq_wf_events_aggregate_version ON workflow_process_events (aggregate_id, version)');

        $this->addSql("COMMENT ON TABLE workflow_process_events IS 'Event store for workflow process instances (append-only)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE workflow_process_events');
    }
}
