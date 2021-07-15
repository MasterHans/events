<?php

use yii\db\Migration;

/**
 * Class m210713_112444_create_table_article
 */
class m210713_112444_create_table_article extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('article', [
            'id' => 'pk',
            'name' => 'varchar(255) DEFAULT NULL',
            'description' => 'text',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('article');
    }
}
