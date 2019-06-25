<?php


use Phinx\Migration\AbstractMigration;

class RemoveMessageStatusFieldTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {
        $this->table('message')->removeColumn('status')
            ->save();

        $this->table('message_member')->addColumn('status', 'enum', ['values' => 'read,unread', 'default' => 'unread', 'comment' => '已读状态'])
            ->save();
    }

    public function down()
    {
        $this->execute('DELETE FROM message');
        $this->execute('DELETE FROM message_member');
    }
}
