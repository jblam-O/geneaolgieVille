<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828223000 extends AbstractMigration
{
    public function getDescription(): string { return 'Store the address of a town event'; }

    public function up(Schema $schema): void
    {
        $schema->getTable('town_event')->addColumn('address', 'string', ['length' => 255, 'notnull' => false]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable('town_event')->dropColumn('address');
    }
}
