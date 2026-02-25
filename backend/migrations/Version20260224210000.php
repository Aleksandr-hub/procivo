<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add manager_id to organization_employees';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization_employees ADD manager_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_emp_manager_id ON organization_employees (manager_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_emp_manager_id');
        $this->addSql('ALTER TABLE organization_employees DROP COLUMN manager_id');
    }
}
