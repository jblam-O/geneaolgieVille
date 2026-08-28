<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304203149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE family (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE gender (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE person (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, birthdate DATETIME NOT NULL, deathdate DATETIME NOT NULL, gender_id INT DEFAULT NULL, childish_union_id INT DEFAULT NULL, INDEX IDX_34DCD176708A0E0 (gender_id), INDEX IDX_34DCD176B5531A7 (childish_union_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE person_person (person_source INT NOT NULL, person_target INT NOT NULL, INDEX IDX_A879E1C0C32F4FC5 (person_source), INDEX IDX_A879E1C0DACA1F4A (person_target), PRIMARY KEY (person_source, person_target)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `union` (id INT AUTO_INCREMENT NOT NULL, startdate DATETIME NOT NULL, enddate DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE union_person (union_id INT NOT NULL, person_id INT NOT NULL, INDEX IDX_FE54B7782C7B5539 (union_id), INDEX IDX_FE54B778217BBB47 (person_id), PRIMARY KEY (union_id, person_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176708A0E0 FOREIGN KEY (gender_id) REFERENCES gender (id)');
        $this->addSql('ALTER TABLE person ADD CONSTRAINT FK_34DCD176B5531A7 FOREIGN KEY (childish_union_id) REFERENCES `union` (id)');
        $this->addSql('ALTER TABLE person_person ADD CONSTRAINT FK_A879E1C0C32F4FC5 FOREIGN KEY (person_source) REFERENCES person (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE person_person ADD CONSTRAINT FK_A879E1C0DACA1F4A FOREIGN KEY (person_target) REFERENCES person (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE union_person ADD CONSTRAINT FK_FE54B7782C7B5539 FOREIGN KEY (union_id) REFERENCES `union` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE union_person ADD CONSTRAINT FK_FE54B778217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE person DROP FOREIGN KEY FK_34DCD176708A0E0');
        $this->addSql('ALTER TABLE person DROP FOREIGN KEY FK_34DCD176B5531A7');
        $this->addSql('ALTER TABLE person_person DROP FOREIGN KEY FK_A879E1C0C32F4FC5');
        $this->addSql('ALTER TABLE person_person DROP FOREIGN KEY FK_A879E1C0DACA1F4A');
        $this->addSql('ALTER TABLE union_person DROP FOREIGN KEY FK_FE54B7782C7B5539');
        $this->addSql('ALTER TABLE union_person DROP FOREIGN KEY FK_FE54B778217BBB47');
        $this->addSql('DROP TABLE family');
        $this->addSql('DROP TABLE gender');
        $this->addSql('DROP TABLE person');
        $this->addSql('DROP TABLE person_person');
        $this->addSql('DROP TABLE `union`');
        $this->addSql('DROP TABLE union_person');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
