<?php
namespace AppBundle\Controller;

use Biz\Activity\Service\ActivityService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\CourseSetService;
use Biz\File\Service\UploadFileService;
use Biz\Task\Service\TaskService;
use Biz\Task\Strategy\BaseStrategy;
use Biz\Task\Strategy\CourseStrategy;
use Biz\Task\Strategy\StrategyContext;
use AppBundle\Util\UploaderToken;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Common\Exception\InvalidArgumentException;

class TaskManageController extends BaseController
{
    public function createAction(Request $request, $courseId)
    {
        $course     = $this->tryManageCourse($courseId);
        $categoryId = $request->query->get('categoryId');
        $chapterId  = $request->query->get('chapterId');
        $taskMode   = $request->query->get('type');
        if ($request->isMethod('POST')) {
            $task               = $request->request->all();
            return $this->createTask($request, $task, $course);
        }
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        return $this->render('task-manage/modal.html.twig', array(
            'mode'       => 'create',
            'course'     => $course,
            'courseSet'  => $courseSet,
            'categoryId' => $categoryId,
            'chapterId'  => $chapterId,
            'taskMode'   => $taskMode,
        ));
    }

    protected function getTaskItemTemplate($course)
    {
        if ($course['isDefault']) {
            return 'task-manage/list-item.html.twig';
        } else {
            return 'task-manage/list-item-lock-mode.html.twig';
        }
    }

    public function batchTaskModalAction(Request $request, $courseId)
    {
        $this->getCourseService()->tryManageCourse($courseId);
        $token  = $request->query->get('token');
        $mode = $request->query->get('mode');
        $parser = new UploaderToken();
        $params = $parser->parse($token);

        if (!$params) {
            return $this->createJsonResponse(array('error' => 'bad token'));
        }

        return $this->render('course-manage/batch-create/batch-create-modal.html.twig', array(
            'token'      => $token,
            'targetType' => $params['targetType'],
            'courseId' => $courseId,
            'mode' => $mode
        ));
    }

    public function createFromFileAction(Request $request, $courseId)
    {
        $fileId = $request->request->get('fileId');
        $mode = $request->query->get('mode');
        $course = $this->getCourseService()->getCourse($courseId);
        $task = $this->createTaskByFileIdAndCourseId($fileId, $course);
        if (!empty($mode)) {
            $task['mode'] = $mode;
        }
        return $this->createTask($request, $task, $course);
    }

    private function createTaskByFileIdAndCourseId($fileId, $course)
    {
        $file = $this->getUploadFileService()->getFile($fileId);
        $task = array(
            'mediaType' => $file['type'],
            'fromCourseId' => $course['id'],
            'courseSetType' => 'normal',
            'media'    => json_encode(array('source' => 'self', 'id' => $fileId, 'name' => $file['filename'])),
            'mediaId'  => $fileId,
            'type'     => $file['type'],
            'length'   => $file['length'],
            'title'    => str_replace(strrchr($file['filename'], '.'), '', $file['filename']),
            'courseSetType' => 'normal',
            'ext' => array('mediaSource' => 'self','mediaId' => $fileId)
        );
        return $task;
    }

    private function createTask(Request $request, $task, $course)
    {
        $task['_base_url']       = $request->getSchemeAndHttpHost();
        $task['fromUserId']      = $this->getUser()->getId();
        $task['fromCourseSetId'] = $course['courseSetId'];

        $task = $this->getTaskService()->createTask($this->parseTimeFields($task));

        if ($course['isDefault'] && isset($task['mode']) && $task['mode'] != 'lesson') {
            return $this->createJsonResponse(array('append' => false));
        }

        return $this->render($this->getTaskItemTemplate($course), array(
            'course' => $course,
            'task'   => $task
        ));
    }

    public function updateAction(Request $request, $courseId, $id)
    {
        $course   = $this->tryManageCourse($courseId);
        $task     = $this->getTaskService()->getTask($id);
        $taskMode = $request->query->get('type');
        if ($task['courseId'] != $courseId) {
            throw new InvalidArgumentException('任务不在计划中');
        }

        if ($request->getMethod() == 'POST') {
            $task              = $request->request->all();
            $task['_base_url'] = $request->getSchemeAndHttpHost();
            $this->getTaskService()->updateTask($id, $this->parseTimeFields($task));
            return $this->createJsonResponse(array('append' => false));
        }

        $activity  = $this->getActivityService()->getActivity($task['activityId']);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        return $this->render('task-manage/modal.html.twig', array(
            'mode'        => 'edit',
            'currentType' => $activity['mediaType'],
            'course'      => $course,
            'courseSet'   => $courseSet,
            'task'        => $task,
            'taskMode'    => $taskMode,
        ));
    }

    public function publishAction(Request $request, $courseId, $id)
    {
        $this->tryManageCourse($courseId);
        $this->getTaskService()->publishTask($id);

        return $this->createJsonResponse(array('success' => true));
    }

    public function unPublishAction(Request $request, $courseId, $id)
    {
        $this->tryManageCourse($courseId);
        $this->getTaskService()->unpublishTask($id);

        return $this->createJsonResponse(array('success' => true));
    }

    public function taskFieldsAction(Request $request, $courseId, $mode)
    {
        $course = $this->tryManageCourse($courseId);

        if ($mode === 'create') {
            $type = $request->query->get('type');
            return $this->forward('AppBundle:Activity/Activity:create', array(
                'courseId' => $courseId,
                'type'     => $type
            ));
        } else {
            $id   = $request->query->get('id');
            $task = $this->getTaskService()->getTask($id);
            return $this->forward('AppBundle:Activity/Activity:update', array(
                'id'       => $task['activityId'],
                'courseId' => $courseId
            ));
        }
    }

    public function deleteAction(Request $request, $courseId, $taskId)
    {
        $course = $this->tryManageCourse($courseId);
        $task   = $this->getTaskService()->getTask($taskId);
        if ($task['courseId'] != $courseId) {
            throw new InvalidArgumentException('任务不在课程中');
        }

        $this->getTaskService()->deleteTask($taskId);
        return $this->createJsonResponse(array('success' => true));
    }

    protected function tryManageCourse($courseId)
    {
        return $this->getCourseService()->tryManageCourse($courseId);
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getActivityConfig()
    {
        return $this->get('extension.default')->getActivities();
    }

    /**
     * @param  $type
     *
     * @return mixed
     */
    protected function getActivityActionConfig($type)
    {
        $config = $this->getActivityConfig();
        return $config[$type]['actions'];
    }

    /**
     * @param $course
     *
     * @return BaseStrategy|CourseStrategy
     */
    protected function createCourseStrategy($course)
    {
        return StrategyContext::getInstance()->createStrategy($course['isDefault'], $this->get('biz'));
    }

    protected function parseTimeFields($fields)
    {
        if (!empty($fields['startTime'])) {
            $fields['startTime'] = strtotime($fields['startTime']);
        }
        if (!empty($fields['endTime'])) {
            $fields['endTime'] = strtotime($fields['endTime']);
        }

        return $fields;
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }
}
