<?php

namespace Topxia\Service\Course\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\Course\Dao\ExerciseDao;

class ExerciseDaoImpl extends BaseDao implements ExerciseDao
{
    protected $table = 'exercise';

    public function getExercise($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

    public function addExercise($fields)
    {   
        $affected = $this->getConnection()->insert($this->table, $fields);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert exercise error.');
        }
        return $this->getExercise($this->getConnection()->lastInsertId());
    }

    public function updateExercise($id, $fields)
    {
        $this->getConnection()->update($this->table, $fields, array('id' => $id));
        return $this->getExercise($id);
    }

    public function findExerciseByCourseIdAndLessonIds($courseId, $lessonIds)
    {   
        if(empty($lessonIds)){
            return array();
        }
        $marks = str_repeat('?,', count($lessonIds) - 1) . '?';
        $sql ="SELECT * FROM {$this->table} WHERE courseId = {$courseId} AND lessonId IN ({$marks});";
        
        return $this->getConnection()->fetchAll($sql, $lessonIds);
    }

}