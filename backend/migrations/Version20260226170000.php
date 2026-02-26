<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add completed_at column to workflow_task_links table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workflow_task_links ADD COLUMN completed_at TIMESTAMP(6) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workflow_task_links DROP COLUMN completed_at');
    }
}
