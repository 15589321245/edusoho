<?php

namespace Biz\TestpaperActivity;

use Topxia\Common\ArrayToolkit;
use Biz\Activity\Config\Activity;
use Topxia\Common\Exception\ResourceNotFoundException;

class TestpaperActivity extends Activity
{
    public function getMetas()
    {
        return array(
            'name' => '参加考试',
            'icon' => 'es-icon es-icon-lesson'
        );
    }

    protected function registerListeners()
    {
        return array(
            'testpaper.finish' => 'Biz\\TestpaperActivity\\Listener\\TestpaperFinishListener'
        );
    }

    public function registerActions()
    {
        return array(
            'create' => 'WebBundle:TestpaperActivity:create',
            'edit'   => 'WebBundle:TestpaperActivity:edit',
            'show'   => 'WebBundle:TestpaperActivity:show'
        );
    }

    public function get($targetId)
    {
        return $this->getTestpaperActivityService()->getActivity($targetId);
    }

    public function create($fields)
    {
        $fields = $this->filterFields($fields);

        return $this->getTestpaperActivityService()->createActivity($fields);
    }

    public function update($targetId, $fields)
    {
        $activity = $this->get($targetId);

        if (!$activity) {
            throw new ResourceNotFoundException('testpaperActivity', $targetId);
        }

        $fields = $this->filterFields($fields);

        return $this->getTestpaperActivityService()->updateActivity($activity['id'], $fields);
    }

    public function delete($targetId)
    {
        return $this->getTestpaperActivityService()->deleteActivity($targetId);
    }

    protected function filterFields($fields)
    {
        $fields = ArrayToolkit::parts($fields, array(
            'mediaId',
            'doTimes',
            'redoInterval',
            'limitedTime',
            'checkType',
            'finishCondition',
            'finisheScore',
            'requireCredit',
            'testMode'
        ));

        $finishCondition = array();

        if (!empty($fields['finishCondition'])) {
            $finishCondition['type'] = $fields['finishCondition'];
        }

        if (!empty($fields['finisheScore'])) {
            $finishCondition['finisheScore'] = $fields['finisheScore'];
            unset($fields['finisheScore']);
        }

        $fields['finishCondition'] = $finishCondition;

        return $fields;
    }

    protected function getTestpaperActivityService()
    {
        return $this->getBiz()->service('TestpaperActivity:TestpaperActivityService');
    }
}
