<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link family trees to authenticated account identifiers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE family ADD account_identifier VARCHAR(180) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_FAMILY_ACCOUNT_IDENTIFIER ON family (account_identifier)');

        $this->addSql("UPDATE family SET account_identifier = 'demo_familles' WHERE name IN ('Maison de Bourbon', 'Maison de Habsbourg')");
        $this->addSql("INSERT INTO family (name, account_identifier) SELECT 'Maison de Bourbon', 'demo_familles' WHERE NOT EXISTS (SELECT 1 FROM family WHERE name = 'Maison de Bourbon')");
        $this->addSql("INSERT INTO family (name, account_identifier) SELECT 'Maison de Habsbourg', 'demo_familles' WHERE NOT EXISTS (SELECT 1 FROM family WHERE name = 'Maison de Habsbourg')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_FAMILY_ACCOUNT_IDENTIFIER ON family');
        $this->addSql('ALTER TABLE family DROP account_identifier');
    }
}
