<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create organization_department_permissions and organization_user_permission_overrides tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE organization_department_permissions (
            id UUID NOT NULL,
            department_id UUID NOT NULL,
            organization_id UUID NOT NULL,
            resource VARCHAR(50) NOT NULL,
            action VARCHAR(50) NOT NULL,
            scope VARCHAR(50) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE UNIQUE INDEX uniq_dept_perm_dept_resource_action ON organization_department_permissions (department_id, resource, action)');
        $this->addSql('CREATE INDEX idx_dept_perm_dept_id ON organization_department_permissions (department_id)');
        $this->addSql('CREATE INDEX idx_dept_perm_org_id ON organization_department_permissions (organization_id)');

        $this->addSql('COMMENT ON COLUMN organization_department_permissions.created_at IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('CREATE TABLE organization_user_permission_overrides (
            id UUID NOT NULL,
            employee_id UUID NOT NULL,
            organization_id UUID NOT NULL,
            resource VARCHAR(50) NOT NULL,
            action VARCHAR(50) NOT NULL,
            effect VARCHAR(10) NOT NULL,
            scope VARCHAR(50) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE UNIQUE INDEX uniq_user_perm_override_emp_resource_action ON organization_user_permission_overrides (employee_id, resource, action)');
        $this->addSql('CREATE INDEX idx_user_perm_override_emp_id ON organization_user_permission_overrides (employee_id)');
        $this->addSql('CREATE INDEX idx_user_perm_override_org_id ON organization_user_permission_overrides (organization_id)');

        $this->addSql('COMMENT ON COLUMN organization_user_permission_overrides.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE organization_user_permission_overrides');
        $this->addSql('DROP TABLE organization_department_permissions');
    }
}
