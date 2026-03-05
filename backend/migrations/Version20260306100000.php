<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create workflow_scheduled_timers table for DB-persistent timer scheduling';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workflow_scheduled_timers (
            id VARCHAR(36) NOT NULL,
            process_instance_id VARCHAR(36) NOT NULL,
            token_id VARCHAR(36) NOT NULL,
            node_id VARCHAR(36) NOT NULL,
            fire_at TIMESTAMP(6) WITHOUT TIME ZONE NOT NULL,
            fired_at TIMESTAMP(6) WITHOUT TIME ZONE DEFAULT NULL,
            dispatched_at TIMESTAMP(6) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(6) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE INDEX idx_wst_instance ON workflow_scheduled_timers (process_instance_id)');
        $this->addSql('CREATE INDEX idx_wst_pending ON workflow_scheduled_timers (fire_at) WHERE fired_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_wst_pending');
        $this->addSql('DROP INDEX IF EXISTS idx_wst_instance');
        $this->addSql('DROP TABLE IF EXISTS workflow_scheduled_timers');
    }
}
