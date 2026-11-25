<?php

declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20261016120000 extends AbstractMigration
{
    public function getDescription(): string { return 'Deduplicate offline synchronization operations per account'; }
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('offline_receipt');
        $table->addColumn('account_identifier', 'string', ['length' => 180]);
        $table->addColumn('operation_id', 'string', ['length' => 36]);
        $table->addColumn('response', 'text');
        $table->setPrimaryKey(['account_identifier', 'operation_id']);
    }
    public function down(Schema $schema): void { $schema->dropTable('offline_receipt'); }
}
