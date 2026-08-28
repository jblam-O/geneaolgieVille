<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260828212000 extends AbstractMigration
{
    public function getDescription(): string { return 'Create towns, historical events and event media'; }

    public function up(Schema $schema): void
    {
        $town = $schema->createTable('town');
        $town->addColumn('id', 'integer', ['autoincrement' => true]);
        $town->addColumn('name', 'string', ['length' => 120]);
        $town->addColumn('slug', 'string', ['length' => 120]);
        $town->addColumn('latitude', 'float');
        $town->addColumn('longitude', 'float');
        $town->setPrimaryKey(['id']);
        $town->addUniqueIndex(['name'], 'UNIQ_TOWN_NAME');
        $town->addUniqueIndex(['slug'], 'UNIQ_TOWN_SLUG');

        $event = $schema->createTable('town_event');
        $event->addColumn('id', 'integer', ['autoincrement' => true]);
        $event->addColumn('town_id', 'integer');
        $event->addColumn('year', 'integer');
        $event->addColumn('date_label', 'string', ['length' => 80]);
        $event->addColumn('title', 'string', ['length' => 180]);
        $event->addColumn('summary', 'text');
        $event->addColumn('detail', 'text');
        $event->addColumn('latitude', 'float', ['notnull' => false]);
        $event->addColumn('longitude', 'float', ['notnull' => false]);
        $event->addColumn('created_at', 'datetime_immutable');
        $event->setPrimaryKey(['id']);
        $event->addIndex(['town_id'], 'IDX_TOWN_EVENT_TOWN');
        $event->addForeignKeyConstraint('town', ['town_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_TOWN_EVENT_TOWN');

        $media = $schema->createTable('town_event_media');
        $media->addColumn('id', 'integer', ['autoincrement' => true]);
        $media->addColumn('event_id', 'integer');
        $media->addColumn('type', 'string', ['length' => 20]);
        $media->addColumn('url', 'string', ['length' => 2048]);
        $media->addColumn('original_name', 'string', ['length' => 255]);
        $media->addColumn('mime_type', 'string', ['length' => 120]);
        $media->setPrimaryKey(['id']);
        $media->addIndex(['event_id'], 'IDX_TOWN_MEDIA_EVENT');
        $media->addForeignKeyConstraint('town_event', ['event_id'], ['id'], ['onDelete' => 'CASCADE'], 'FK_TOWN_MEDIA_EVENT');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('town_event_media');
        $schema->dropTable('town_event');
        $schema->dropTable('town');
    }
}
