<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224143544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE organization_departments (organization_id VARCHAR(36) NOT NULL, parent_id VARCHAR(36) DEFAULT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(50) NOT NULL, description TEXT DEFAULT NULL, sort_order INT NOT NULL, level INT NOT NULL, path VARCHAR(2048) NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_dept_org_id ON organization_departments (organization_id)');
        $this->addSql('CREATE INDEX idx_dept_parent_id ON organization_departments (parent_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_dept_code_org ON organization_departments (code, organization_id)');
        $this->addSql('CREATE TABLE organization_employees (organization_id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL, position_id VARCHAR(36) NOT NULL, department_id VARCHAR(36) NOT NULL, employee_number VARCHAR(50) NOT NULL, hired_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_emp_org_id ON organization_employees (organization_id)');
        $this->addSql('CREATE INDEX idx_emp_user_id ON organization_employees (user_id)');
        $this->addSql('CREATE INDEX idx_emp_dept_id ON organization_employees (department_id)');
        $this->addSql('CREATE INDEX idx_emp_pos_id ON organization_employees (position_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_emp_user_org ON organization_employees (user_id, organization_id)');
        $this->addSql('CREATE TABLE organization_organizations (name VARCHAR(255) NOT NULL, slug VARCHAR(63) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, owner_user_id VARCHAR(36) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5F0CEBEF989D9B62 ON organization_organizations (slug)');
        $this->addSql('CREATE INDEX idx_org_owner ON organization_organizations (owner_user_id)');
        $this->addSql('CREATE TABLE organization_positions (organization_id VARCHAR(36) NOT NULL, department_id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, sort_order INT NOT NULL, is_head BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, id VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_pos_org_id ON organization_positions (organization_id)');
        $this->addSql('CREATE INDEX idx_pos_dept_id ON organization_positions (department_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE organization_departments');
        $this->addSql('DROP TABLE organization_employees');
        $this->addSql('DROP TABLE organization_organizations');
        $this->addSql('DROP TABLE organization_positions');
    }
}
