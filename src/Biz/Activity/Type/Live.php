<?php

namespace Biz\Activity\Type;

use Biz\Activity\Config\Activity;
use Biz\Activity\Service\LiveActivityService;
use Biz\Activity\Service\ActivityLearnLogService;

class Live extends Activity
{
    protected function registerListeners()
    {
        return array(
        );
    }

    public function create($fields)
    {
        return $this->getLiveActivityService()->createLiveActivity($fields);
    }

    public function copy($activity, $config = array())
    {
        $biz = $this->getBiz();
        $live = $this->getLiveActivityService()->getLiveActivity($activity['mediaId']);
        if (empty($config['refLiveroom'])) {
            $activity['fromUserId'] = $biz['user']['id'];
            unset($activity['id']);
            unset($activity['startTime']);
            unset($activity['endTime']);

            return $this->getLiveActivityService()->createLiveActivity($activity, true);
        }

        return $live;
    }

    public function sync($sourceActivity, $activity)
    {
        //引用的是同一个直播教室，无需同步
        return null;
    }

    public function allowTaskAutoStart($activity)
    {
        return $activity['startTime'] <= time() && $activity['endTime'] >= time();
    }

    public function update($id, &$fields, $activity)
    {
        return $this->getLiveActivityService()->updateLiveActivity($id, $fields, $activity);
    }

    public function get($targetId)
    {
        return $this->getLiveActivityService()->getLiveActivity($targetId);
    }

    public function find($targetIds)
    {
        return $this->getLiveActivityService()->findLiveActivitiesByIds($targetIds);
    }

    public function delete($targetId)
    {
        return $this->getLiveActivityService()->deleteLiveActivity($targetId);
    }

    public function isFinished($activityId)
    {
        $result = $this->getActivityLearnLogService()->findMyLearnLogsByActivityIdAndEvent($activityId, 'finish');

        return !empty($result);
    }

    /**
     * @return LiveActivityService
     */
    protected function getLiveActivityService()
    {
        return $this->getBiz()->service('Activity:LiveActivityService');
    }

    /**
     * @return ActivityLearnLogService
     */
    protected function getActivityLearnLogService()
    {
        return $this->getBiz()->service('Activity:ActivityLearnLogService');
    }
}
