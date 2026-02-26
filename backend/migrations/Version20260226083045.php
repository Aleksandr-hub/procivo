<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260226083045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Workflow module tables: process definitions, versions, nodes, transitions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workflow_process_definitions (id VARCHAR(36) NOT NULL, organization_id VARCHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_by VARCHAR(36) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_wf_def_organization ON workflow_process_definitions (organization_id)');
        $this->addSql('CREATE INDEX idx_wf_def_status ON workflow_process_definitions (status)');

        $this->addSql('CREATE TABLE workflow_process_definition_versions (id VARCHAR(36) NOT NULL, process_definition_id VARCHAR(36) NOT NULL, version_number INT NOT NULL, nodes_snapshot JSON NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, published_by VARCHAR(36) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_wf_ver_definition ON workflow_process_definition_versions (process_definition_id)');
        $this->addSql('CREATE INDEX idx_wf_ver_number ON workflow_process_definition_versions (process_definition_id, version_number)');

        $this->addSql('CREATE TABLE workflow_nodes (id VARCHAR(36) NOT NULL, process_definition_id VARCHAR(36) NOT NULL, type VARCHAR(30) NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, config JSON NOT NULL, position_x DOUBLE PRECISION NOT NULL, position_y DOUBLE PRECISION NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_wf_node_definition ON workflow_nodes (process_definition_id)');
        $this->addSql('CREATE INDEX idx_wf_node_type ON workflow_nodes (process_definition_id, type)');

        $this->addSql('CREATE TABLE workflow_transitions (id VARCHAR(36) NOT NULL, process_definition_id VARCHAR(36) NOT NULL, source_node_id VARCHAR(36) NOT NULL, target_node_id VARCHAR(36) NOT NULL, name VARCHAR(255) DEFAULT NULL, condition_expression TEXT DEFAULT NULL, sort_order INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_wf_trans_definition ON workflow_transitions (process_definition_id)');
        $this->addSql('CREATE INDEX idx_wf_trans_source ON workflow_transitions (source_node_id)');
        $this->addSql('CREATE INDEX idx_wf_trans_target ON workflow_transitions (target_node_id)');

        $this->addSql("COMMENT ON TABLE workflow_process_definitions IS 'BPMN process definition templates'");
        $this->addSql("COMMENT ON TABLE workflow_process_definition_versions IS 'Immutable snapshots of process definitions at publish time'");
        $this->addSql("COMMENT ON TABLE workflow_nodes IS 'BPMN nodes within a process definition'");
        $this->addSql("COMMENT ON TABLE workflow_transitions IS 'Transitions (edges) between nodes'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE workflow_transitions');
        $this->addSql('DROP TABLE workflow_nodes');
        $this->addSql('DROP TABLE workflow_process_definition_versions');
        $this->addSql('DROP TABLE workflow_process_definitions');
    }
}
