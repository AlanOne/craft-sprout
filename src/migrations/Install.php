<?php

namespace alanjancic\sprout\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable('{{%sprout_log}}', [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'elementType' => $this->string()->notNull(),
            'seederName' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, '{{%sprout_log}}', ['seederName']);
        $this->createIndex(null, '{{%sprout_log}}', ['elementId']);

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%sprout_log}}');
        return true;
    }
}
