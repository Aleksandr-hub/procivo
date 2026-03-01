<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260302100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create audit_log table for append-only audit trail';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE audit_log (
                id VARCHAR(36) NOT NULL,
                event_type VARCHAR(100) NOT NULL,
                actor_id VARCHAR(36) DEFAULT NULL,
                entity_type VARCHAR(50) NOT NULL,
                entity_id VARCHAR(36) NOT NULL,
                organization_id VARCHAR(36) DEFAULT NULL,
                changes JSONB DEFAULT NULL,
                occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);

        $this->addSql('CREATE INDEX idx_audit_log_entity ON audit_log (entity_type, entity_id, occurred_at)');
        $this->addSql('CREATE INDEX idx_audit_log_actor ON audit_log (actor_id, occurred_at)');
        $this->addSql('CREATE INDEX idx_audit_log_org ON audit_log (organization_id, occurred_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_log');
    }
}
