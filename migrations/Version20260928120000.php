<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow incomplete people records and store places and life events';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE person MODIFY birthdate DATETIME DEFAULT NULL, MODIFY deathdate DATETIME DEFAULT NULL, ADD birth_place VARCHAR(255) DEFAULT NULL, ADD death_place VARCHAR(255) DEFAULT NULL, ADD life_events JSON NULL');
        $this->addSql("UPDATE person SET life_events = '[]' WHERE life_events IS NULL");
        $this->addSql('ALTER TABLE person MODIFY life_events JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE person DROP birth_place, DROP death_place, DROP life_events, MODIFY birthdate DATETIME NOT NULL, MODIFY deathdate DATETIME NOT NULL');
    }
}
