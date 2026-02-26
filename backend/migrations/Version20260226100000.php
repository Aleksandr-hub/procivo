<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notifications table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notifications (id VARCHAR(36) NOT NULL, recipient_id VARCHAR(36) NOT NULL, type VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, body TEXT NOT NULL, related_entity_id VARCHAR(36) DEFAULT NULL, is_read BOOLEAN NOT NULL DEFAULT FALSE, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_notification_recipient ON notifications (recipient_id, created_at)');
        $this->addSql('CREATE INDEX idx_notification_unread ON notifications (recipient_id, is_read)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notifications');
    }
}
