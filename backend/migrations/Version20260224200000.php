<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create organization_invitations table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE organization_invitations (id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, email VARCHAR(255) NOT NULL, department_id VARCHAR(36) NOT NULL, position_id VARCHAR(36) NOT NULL, employee_number VARCHAR(50) NOT NULL, token VARCHAR(128) NOT NULL, status VARCHAR(20) NOT NULL, invited_by_user_id VARCHAR(36) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, accepted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_inv_token ON organization_invitations (token)');
        $this->addSql('CREATE INDEX idx_inv_org_id ON organization_invitations (organization_id)');
        $this->addSql('CREATE INDEX idx_inv_email ON organization_invitations (email)');
        $this->addSql('CREATE INDEX idx_inv_status ON organization_invitations (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE organization_invitations');
    }
}
