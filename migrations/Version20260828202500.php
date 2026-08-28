<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828202500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link events to periods and add database-backed display labels';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE civilization ADD label VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE civilization SET label = name WHERE label IS NULL');
        $this->addSql('ALTER TABLE civilization CHANGE label label VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE period ADD label VARCHAR(255) DEFAULT NULL');
        $this->addSql("INSERT INTO period (name, color, label) SELECT DISTINCT e.period, '#d4af37', e.period FROM events e LEFT JOIN period p ON p.name = e.period WHERE p.id IS NULL");
        $this->addSql('UPDATE period SET label = name WHERE label IS NULL');
        $this->addSql('ALTER TABLE period CHANGE label label VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE events ADD period_id INT DEFAULT NULL');
        $this->addSql('UPDATE events e INNER JOIN period p ON p.name = e.period SET e.period_id = p.id');
        $this->addSql('ALTER TABLE events DROP period');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574AEC8B7ADE FOREIGN KEY (period_id) REFERENCES period (id)');
        $this->addSql('CREATE INDEX IDX_5387574AEC8B7ADE ON events (period_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events ADD period VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE events e INNER JOIN period p ON p.id = e.period_id SET e.period = p.name');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574AEC8B7ADE');
        $this->addSql('DROP INDEX IDX_5387574AEC8B7ADE ON events');
        $this->addSql('ALTER TABLE events DROP period_id');
        $this->addSql('ALTER TABLE events CHANGE period period VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE civilization DROP label');
        $this->addSql('ALTER TABLE period DROP label');
    }
}
