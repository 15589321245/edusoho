<?php

use Phpmig\Migration\Migration;

class C2CoursesetCover extends Migration
{
    /**
     * Do the migration.
     */
    public function up()
    {
        $biz = $this->getContainer();
        $db = $biz['db'];
        $db->exec('
            ALTER TABLE c2_course_set ADD COLUMN cover VARCHAR(1024);
        ');
    }

    /**
     * Undo the migration.
     */
    public function down()
    {
        $biz = $this->getContainer();
        $db = $biz['db'];
        $db->exec('
            ALTER TABLE c2_course_set DROP COLUMN cover;
        ');
    }
}
