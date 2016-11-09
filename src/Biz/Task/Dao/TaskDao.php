<?php

namespace Biz\Task\Dao;

use Codeages\Biz\Framework\Dao\GeneralDaoInterface;

interface TaskDao extends GeneralDaoInterface
{
    public function findByCourseId($courseId);

    public function getByCourseIdAndActivityId($courseId, $activity);
}
