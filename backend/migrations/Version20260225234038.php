<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225234038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task_manager_task_attachments table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_manager_task_attachments (id VARCHAR(36) NOT NULL, task_id VARCHAR(36) NOT NULL, original_name VARCHAR(255) NOT NULL, storage_path VARCHAR(500) NOT NULL, mime_type VARCHAR(100) NOT NULL, file_size INT NOT NULL, uploaded_by VARCHAR(36) NOT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_attachment_task ON task_manager_task_attachments (task_id)');
        $this->addSql('CREATE INDEX idx_attachment_uploaded ON task_manager_task_attachments (task_id, uploaded_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task_manager_task_attachments');
    }
}
