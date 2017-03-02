<?php
namespace AppBundle\Controller\Activity;

use Biz\Course\Service\CourseService;
use AppBundle\Controller\BaseController;
use Biz\Activity\Service\ActivityService;
use Symfony\Component\HttpFoundation\Request;

class ActivityController extends BaseController
{

    public function showAction($task, $preview)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);

        if (empty($activity)) {
            throw $this->createNotFoundException('activity not found');
        }
        $actionConfig = $this->getActivityActionConfig($activity['mediaType']);
        return $this->forward($actionConfig['show'], array(
            'activity' => $activity,
            'preview'  => $preview,
        ));
    }

    public function previewAction($task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        if (empty($activity)) {
            throw $this->createNotFoundException('activity not found');
        }
        $actionConfig = $this->getActivityActionConfig($activity['mediaType']);
        return $this->forward($actionConfig['preview'], array(
            'task' => $task
        ));
    }

    public function updateAction($id, $courseId)
    {
        $activity     = $this->getActivityService()->getActivity($id);
        $actionConfig = $this->getActivityActionConfig($activity['mediaType']);
        return $this->forward($actionConfig['edit'], array(
            'id'       => $activity['id'],
            'courseId' => $courseId,
        ));
    }

    public function createAction($type, $courseId)
    {
        $actionConfig = $this->getActivityActionConfig($type);
        return $this->forward($actionConfig['create'], array(
            'courseId' => $courseId
        ));
    }

    public function triggerAction(Request $request, $courseId, $activityId)
    {
        $this->getCourseService()->tryTakeCourse($courseId);

        $activity = $this->getActivityService()->getActivity($activityId);

        if (empty($activity)) {
            throw $this->createResourceNotFoundException('activity', $activityId);
        }

        $eventName = $request->request->get('eventName');

        if (empty($eventName)) {
            throw $this->createNotFoundException('activity event is empty');
        }

        $data = $request->request->get('data', array());

        $this->getActivityService()->trigger($activityId, $eventName, $data);

        return $this->createJsonResponse(array(
            'event' => $eventName,
            'data'  => $data,
        ));
    }

    protected function getActivityConfig()
    {
        return $this->get('extension.default')->getActivities();
    }

    protected function getActivityActionConfig($type)
    {
        $config = $this->getActivityConfig();
        return $config[$type]['actions'];
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }
}
