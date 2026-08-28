<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260401112138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE events (id INT AUTO_INCREMENT NOT NULL, year INT NOT NULL, label VARCHAR(255) NOT NULL, civilization VARCHAR(255) NOT NULL, period VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, summary VARCHAR(255) NOT NULL, detail VARCHAR(255) NOT NULL, emoji VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE events_person (events_id INT NOT NULL, person_id INT NOT NULL, INDEX IDX_E661E8439D6A1065 (events_id), INDEX IDX_E661E843217BBB47 (person_id), PRIMARY KEY (events_id, person_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE events_person ADD CONSTRAINT FK_E661E8439D6A1065 FOREIGN KEY (events_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE events_person ADD CONSTRAINT FK_E661E843217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events_person DROP FOREIGN KEY FK_E661E8439D6A1065');
        $this->addSql('ALTER TABLE events_person DROP FOREIGN KEY FK_E661E843217BBB47');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE events_person');
    }
}
