<?php

namespace Biz\Activity\Dao;

use Codeages\Biz\Framework\Dao\GeneralDaoInterface;

interface LiveActivityDao extends GeneralDaoInterface
{
    public function findLiveActivitiesByIds(array $ids);
}
