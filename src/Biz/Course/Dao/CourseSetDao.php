<?php

namespace Biz\Course\Dao;

use Codeages\Biz\Framework\Dao\GeneralDaoInterface;

interface CourseSetDao extends GeneralDaoInterface
{
    const TABLENAME = 'c2_course_set';

    public function findByIds(array $ids);

    public function findLikeTitle($title);

    public function findCourseSetsByParentIdAndLocked($parentId, $locked);

    public function analysisCourseSetDataByTime($startTime, $endTime);
}
