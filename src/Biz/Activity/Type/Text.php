<?php

namespace Biz\Activity\Type;

use Biz\Activity\Dao\TextActivityDao;
use Biz\Activity\Service\ActivityService;
use AppBundle\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;
use Biz\Activity\Service\ActivityService;
use Biz\Activity\Service\ActivityLearnLogService;

class Text extends Activity
{
    protected function registerListeners()
    {
        return array();
    }

    public function get($targetId)
    {
        return $this->getTextActivityDao()->get($targetId);
    }

    public function copy($activity, $config = array())
    {
        $biz     = $this->getBiz();
        $text    = $this->getTextActivityDao()->get($activity['mediaId']);
        $newText = array(
            'finishType'    => $text['finishType'],
            'finishDetail'  => $text['finishDetail'],
            'createdUserId' => $biz['user']['id']
        );
        return $this->getTextActivityDao()->create($newText);
    }

    public function sync($sourceActivity, $activity)
    {
        $sourceText           = $this->getTextActivityDao()->get($sourceActivity['mediaId']);
        $text                 = $this->getTextActivityDao()->get($activity['mediaId']);
        $text['finishType']   = $sourceText['finishType'];
        $text['finishDetail'] = $sourceText['finishDetail'];

        return $this->getTextActivityDao()->update($text['id'], $text);
    }

    public function update($targetId, &$fields, $activity)
    {
        $text = ArrayToolkit::parts($fields, array(
            'finishType',
            'finishDetail'
        ));

        $biz                   = $this->getBiz();
        $text['createdUserId'] = $biz['user']['id'];
        return $this->getTextActivityDao()->update($targetId, $text);
    }

    public function isFinished($activityId)
    {
        $result       = $this->getActivityLearnLogService()->sumMyLearnedTimeByActivityId($activityId);
        $activity     = $this->getActivityService()->getActivity($activityId);
        $textActivity = $this->getTextActivityDao()->get($activity['mediaId']);

        return !empty($result)
            && $textActivity['finishType'] == 'time'
            && $result >= $textActivity['finishDetail'];
    }

    public function delete($targetId)
    {
        return $this->getTextActivityDao()->delete($targetId);
    }

    public function create($fields)
    {
        $text = ArrayToolkit::parts($fields, array(
            'finishType',
            'finishDetail'
        ));
        $biz                   = $this->getBiz();
        $text['createdUserId'] = $biz['user']['id'];
        return $this->getTextActivityDao()->create($text);
    }

    /**
     * @return TextActivityDao
     */
    protected function getTextActivityDao()
    {
        return $this->getBiz()->dao('Activity:TextActivityDao');
    }

    /**
     * @return ActivityLearnLogService
     */
    protected function getActivityLearnLogService()
    {
        return $this->getBiz()->service('Activity:ActivityLearnLogService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->getBiz()->service('Activity:ActivityService');
    }

}
