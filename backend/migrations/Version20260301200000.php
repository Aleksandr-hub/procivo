<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add channel, related_entity_type, read_at to notifications; create notification_preferences table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE notifications
                ADD channel VARCHAR(20) NOT NULL DEFAULT 'in_app',
                ADD related_entity_type VARCHAR(30) DEFAULT NULL,
                ADD read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE notification_preferences (
                id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                event_type VARCHAR(50) NOT NULL,
                channel VARCHAR(20) NOT NULL,
                enabled BOOLEAN NOT NULL DEFAULT TRUE,
                PRIMARY KEY(id)
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uq_notif_pref_user_event_channel ON notification_preferences (user_id, event_type, channel)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX idx_notif_pref_user ON notification_preferences (user_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE notification_preferences
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE notifications
                DROP COLUMN channel,
                DROP COLUMN related_entity_type,
                DROP COLUMN read_at
        SQL);
    }
}
