<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225224307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task_manager_task_assignments table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_manager_task_assignments (id VARCHAR(36) NOT NULL, task_id VARCHAR(36) NOT NULL, employee_id VARCHAR(36) NOT NULL, role VARCHAR(20) NOT NULL, assigned_by VARCHAR(36) NOT NULL, assigned_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_assignment_task ON task_manager_task_assignments (task_id)');
        $this->addSql('CREATE INDEX idx_assignment_employee ON task_manager_task_assignments (employee_id)');
        $this->addSql('CREATE INDEX idx_assignment_unique ON task_manager_task_assignments (task_id, employee_id, role)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task_manager_task_assignments');
    }
}
