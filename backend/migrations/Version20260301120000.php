<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar_path column to identity_users for S3 avatar storage';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE identity_users ADD COLUMN avatar_path VARCHAR(500) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE identity_users DROP COLUMN avatar_path');
    }
}
