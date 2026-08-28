<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260826162312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events ADD civilization_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT FK_5387574A6946BDDB FOREIGN KEY (civilization_id) REFERENCES civilization (id)');
        $this->addSql('CREATE INDEX IDX_5387574A6946BDDB ON events (civilization_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY FK_5387574A6946BDDB');
        $this->addSql('DROP INDEX IDX_5387574A6946BDDB ON events');
        $this->addSql('ALTER TABLE events DROP civilization_id');
    }
}
