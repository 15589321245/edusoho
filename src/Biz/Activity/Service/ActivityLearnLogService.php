<?php

namespace Biz\Activity\Service;

interface ActivityLearnLogService
{
    public function createLog($activity, $eventName, $data);
}
