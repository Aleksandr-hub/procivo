<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260228100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add form_schema JSONB column to task_manager_tasks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task_manager_tasks ADD COLUMN form_schema JSONB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task_manager_tasks DROP COLUMN form_schema');
    }
}
