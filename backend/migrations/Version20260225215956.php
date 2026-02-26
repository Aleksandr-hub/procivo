<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225215956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task_manager_labels and task_manager_task_labels tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_manager_labels (id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, color VARCHAR(7) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_label_org ON task_manager_labels (organization_id)');

        $this->addSql('CREATE TABLE task_manager_task_labels (task_id VARCHAR(36) NOT NULL, label_id VARCHAR(36) NOT NULL, PRIMARY KEY (task_id, label_id))');
        $this->addSql('CREATE INDEX idx_task_label_task ON task_manager_task_labels (task_id)');
        $this->addSql('CREATE INDEX idx_task_label_label ON task_manager_task_labels (label_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task_manager_task_labels');
        $this->addSql('DROP TABLE task_manager_labels');
    }
}
