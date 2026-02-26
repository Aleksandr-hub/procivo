<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260225214939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task_manager_comments table for threaded comments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_manager_comments (id VARCHAR(36) NOT NULL, task_id VARCHAR(36) NOT NULL, author_id VARCHAR(36) NOT NULL, parent_id VARCHAR(36) DEFAULT NULL, body TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_comment_task ON task_manager_comments (task_id)');
        $this->addSql('CREATE INDEX idx_comment_author ON task_manager_comments (author_id)');
        $this->addSql('CREATE INDEX idx_comment_parent ON task_manager_comments (parent_id)');
        $this->addSql('CREATE INDEX idx_comment_created ON task_manager_comments (task_id, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task_manager_comments');
    }
}
