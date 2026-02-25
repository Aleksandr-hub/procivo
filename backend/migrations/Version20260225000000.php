<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create RBAC tables: organization_roles, organization_permissions, organization_employee_roles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE organization_roles (
                id VARCHAR(36) NOT NULL,
                organization_id VARCHAR(36) NOT NULL,
                name VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                is_system BOOLEAN NOT NULL DEFAULT FALSE,
                hierarchy INTEGER NOT NULL DEFAULT 0,
                created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ');
        $this->addSql('CREATE INDEX idx_role_org_id ON organization_roles (organization_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_role_name_org ON organization_roles (name, organization_id)');
        $this->addSql('ALTER TABLE organization_roles ADD CONSTRAINT fk_role_org FOREIGN KEY (organization_id) REFERENCES organization_organizations (id) ON DELETE CASCADE');

        $this->addSql('
            CREATE TABLE organization_permissions (
                id VARCHAR(36) NOT NULL,
                role_id VARCHAR(36) NOT NULL,
                organization_id VARCHAR(36) NOT NULL,
                resource VARCHAR(50) NOT NULL,
                action VARCHAR(50) NOT NULL,
                scope VARCHAR(50) NOT NULL,
                created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ');
        $this->addSql('CREATE INDEX idx_perm_role_id ON organization_permissions (role_id)');
        $this->addSql('CREATE INDEX idx_perm_org_id ON organization_permissions (organization_id)');
        $this->addSql('CREATE INDEX idx_perm_resource_action ON organization_permissions (resource, action)');
        $this->addSql('CREATE UNIQUE INDEX uniq_perm_role_resource_action ON organization_permissions (role_id, resource, action)');
        $this->addSql('ALTER TABLE organization_permissions ADD CONSTRAINT fk_perm_role FOREIGN KEY (role_id) REFERENCES organization_roles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE organization_permissions ADD CONSTRAINT fk_perm_org FOREIGN KEY (organization_id) REFERENCES organization_organizations (id) ON DELETE CASCADE');

        $this->addSql('
            CREATE TABLE organization_employee_roles (
                id VARCHAR(36) NOT NULL,
                employee_id VARCHAR(36) NOT NULL,
                role_id VARCHAR(36) NOT NULL,
                organization_id VARCHAR(36) NOT NULL,
                assigned_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ');
        $this->addSql('CREATE INDEX idx_emp_role_emp_id ON organization_employee_roles (employee_id)');
        $this->addSql('CREATE INDEX idx_emp_role_role_id ON organization_employee_roles (role_id)');
        $this->addSql('CREATE INDEX idx_emp_role_org_id ON organization_employee_roles (organization_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_emp_role ON organization_employee_roles (employee_id, role_id)');
        $this->addSql('ALTER TABLE organization_employee_roles ADD CONSTRAINT fk_emp_role_emp FOREIGN KEY (employee_id) REFERENCES organization_employees (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE organization_employee_roles ADD CONSTRAINT fk_emp_role_role FOREIGN KEY (role_id) REFERENCES organization_roles (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE organization_employee_roles');
        $this->addSql('DROP TABLE organization_permissions');
        $this->addSql('DROP TABLE organization_roles');
    }
}
