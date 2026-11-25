<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905164500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create authenticated users for family trees and seed the demo account';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE app_user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_APP_USER_USERNAME (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $password = '$2y$12$lmyF5ugAIW0hRchkXgQXrOSRsuoK77sr3YDyA0ceF9pej2YoPJP2W';
        $this->addSql(sprintf(
            "INSERT INTO app_user (username, roles, password) VALUES ('demo_familles', '[\"ROLE_FAMILY\"]', '%s')",
            $password
        ));
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_user');
    }
}
