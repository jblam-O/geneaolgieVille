<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261013120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store photo URLs on people';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE person ADD photo_urls JSON DEFAULT NULL');
        $this->addSql("UPDATE person SET photo_urls = '[]' WHERE photo_urls IS NULL");
        $this->addSql('ALTER TABLE person MODIFY photo_urls JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE person DROP photo_urls');
    }
}
