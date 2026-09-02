<?php

namespace alanjancic\seeder\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable('{{%seeder_log}}', [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'elementType' => $this->string()->notNull(),
            'seederName' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%seeder_log}}', ['seederName']);
        $this->createIndex(null, '{{%seeder_log}}', ['elementId']);

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%seeder_log}}');
        return true;
    }
}
