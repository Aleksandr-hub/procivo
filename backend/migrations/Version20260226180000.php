<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add node_name column to workflow_task_links table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE workflow_task_links ADD COLUMN node_name VARCHAR(255) NOT NULL DEFAULT ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workflow_task_links DROP COLUMN node_name');
    }
}
