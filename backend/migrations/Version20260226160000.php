<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add action_key and form_fields columns to workflow_transitions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workflow_transitions ADD COLUMN action_key VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE workflow_transitions ADD COLUMN form_fields JSONB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workflow_transitions DROP COLUMN form_fields');
        $this->addSql('ALTER TABLE workflow_transitions DROP COLUMN action_key');
    }
}
