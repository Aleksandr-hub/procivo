<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add assignment strategy and candidate pool columns to task_manager_tasks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE task_manager_tasks ADD COLUMN assignment_strategy VARCHAR(30) NOT NULL DEFAULT 'unassigned'");
        $this->addSql('ALTER TABLE task_manager_tasks ADD COLUMN candidate_role_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE task_manager_tasks ADD COLUMN candidate_department_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_task_candidate_role ON task_manager_tasks (candidate_role_id)');
        $this->addSql('CREATE INDEX idx_task_candidate_dept ON task_manager_tasks (candidate_department_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_task_candidate_dept');
        $this->addSql('DROP INDEX idx_task_candidate_role');
        $this->addSql('ALTER TABLE task_manager_tasks DROP COLUMN candidate_department_id');
        $this->addSql('ALTER TABLE task_manager_tasks DROP COLUMN candidate_role_id');
        $this->addSql('ALTER TABLE task_manager_tasks DROP COLUMN assignment_strategy');
    }
}
