<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260307200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add TOTP two-factor authentication fields to identity_users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE identity_users ADD totp_secret TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE identity_users ADD totp_enabled BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE identity_users ADD backup_codes JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE identity_users DROP COLUMN totp_secret');
        $this->addSql('ALTER TABLE identity_users DROP COLUMN totp_enabled');
        $this->addSql('ALTER TABLE identity_users DROP COLUMN backup_codes');
    }
}
