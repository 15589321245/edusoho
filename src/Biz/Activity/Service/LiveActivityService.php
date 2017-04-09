<?php

namespace Biz\Activity\Service;

interface LiveActivityService
{
    public function getLiveActivity($id);

    public function createLiveActivity($activity, $ignoreValidation = false);

    public function updateLiveActivity($id, &$fields, $activity);

    public function deleteLiveActivity($id);

    public function createLiveroom($activity);

    public function findLiveActivityByIds(array $ids);
}
