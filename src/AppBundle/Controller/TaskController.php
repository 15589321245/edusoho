<?php
namespace AppBundle\Controller;

use Biz\Task\Service\TaskService;
use Biz\User\Service\TokenService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\MemberService;
use Biz\Task\Service\TaskResultService;
use Biz\Course\Service\CourseSetService;
use Biz\Activity\Service\ActivityService;
use Symfony\Component\HttpFoundation\Request;

class TaskController extends BaseController
{
    public function showAction(Request $request, $courseId, $id)
    {
        $preview = $request->query->get('preview');

        $task = $this->tryLearnTask($courseId, $id, (bool)$preview);

        $user   = $this->getCurrentUser();
        $course = $this->getCourseService()->getCourse($courseId);
        $member = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);

        if ($member && !$this->getCourseMemberService()->isMemberNonExpired($course, $member)) {
            return $this->redirect($this->generateUrl('my_course_show', array('id' => $courseId)));
        }

        if ($this->canStartTask($task)) {
            $this->getActivityService()->trigger($task['activityId'], 'start', array(
                'task' => $task
            ));
        }

        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($id);
        if (empty($taskResult)) {
            $taskResult = array('status' => 'none');
        }
        if ($taskResult['status'] == 'finish') {
            list($course, $nextTask, $finishedRate) = $this->getNextTaskAndFinishedRate($task);
        }
        return $this->render('task/show.html.twig', array(
            'course'       => $course,
            'task'         => $task,
            'taskResult'   => $taskResult,
            'nextTask'     => empty($nextTask) ? array() : $nextTask,
            'finishedRate' => empty($finishedRate) ? 0 : $finishedRate
        ));
    }

    private function canStartTask($task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        $config   = $this->getActivityService()->getActivityConfig($activity['mediaType']);
        return $config->allowTaskAutoStart($activity);
    }

    public function previewAction($courseId, $id)
    {
        $course = $this->getCourseService()->getCourse($courseId);

        $task = $this->getTaskService()->getTask($id);

        $user = $this->getCurrentUser();

        if (empty($task) || $task['courseId'] != $courseId) {
            return $this->createNotFoundException('task is not exist');
        }

        //课程不可购买，且任务不免费
        if (empty($task['isFree']) && empty($course['buyable']) && empty($course['tryLookable'])) {
            return $this->render('task/preview-notice-modal.html.twig', array('course' => $course));
        }

        //课程关闭
        if (!empty($course['status']) && $course['status'] == 'closed') {
            return $this->render('task/preview-notice-modal.html.twig', array('course' => $course));
        }

        //课时不免费并且不满足：
        // 1. 有时间限制设置
        // 2. 课时为视频课时
        // 3. 视频课时非优酷等外链视频时提示购买
        $taskCanTryLook = $course['tryLookable'] && $task['type'] == 'video' && $task['mediaSource'] == 'self';
        if (empty($task['isFree']) && !$taskCanTryLook) {
            if (!$user->isLogin()) {
                throw $this->createAccessDeniedException();
            }
            if ($course["parentId"] > 0) {
                return $this->redirect($this->generateUrl('classroom_buy_hint', array('courseId' => $course["id"])));
            }

            return $this->forward('AppBundle:Course/CourseOrder:buy', array('id' => $courseId), array('preview' => true, 'lessonId' => $task['id']));
        }

        //在可预览情况下查看网站设置是否可匿名预览
        $allowAnonymousPreview = $this->setting('course.allowAnonymousPreview', 1);

        if (empty($allowAnonymousPreview) && !$user->isLogin()) {
            throw $this->createAccessDeniedException();
        }

        //TODO vip 插件改造 判断用户是否为VIP

        return $this->render('task/preview.html.twig', array(
            'course'    => $course,
            'task'      => $task,
            'user'      => $user,
            'vipStatus' => false
        ));
    }

    public function contentPreviewAction($courseId, $id)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        $task   = $this->getTaskService()->getTask($id);

        if (empty($task) || $task['courseId'] != $courseId) {
            throw $this->createNotFoundException('task is not exist');
        }

        if (!$this->canPreviewTask($task, $course)) {
            throw $this->createAccessDeniedException('task is not free');
        }

        return $this->forward('AppBundle:Activity/Activity:preview', array('task' => $task));
    }

    private function canPreviewTask($task, $course)
    {
        if ($task['isFree']) {
            return true;
        }
        $activity = $this->getActivityService()->getActivity($task['activityId'], true);
        if (empty($course['tryLookable']) || $activity['mediaType'] != 'video') {
            return false;
        }
        return $activity['ext']['mediaSource'] == 'cloud';
    }

    public function qrcodeAction(Request $request, $courseId, $id)
    {
        $user = $this->getCurrentUser();
        $host = $request->getSchemeAndHttpHost();

        //TODO 移动端学习 api 重构
        if ($user->isLogin()) {
            $appUrl = "{$host}/mapi_v2/mobile/main#/lesson/{$courseId}/{$id}";
        } else {
            $appUrl = "{$host}/mapi_v2/mobile/main#/course/{$courseId}";
        }

        $token = $this->getTokenService()->makeToken('qrcode', array(
            'userId'   => $user['id'],
            'data'     => array(
                'url'    => $this->generateUrl('course_task_show', array('courseId' => $courseId, 'id' => $id), true),
                'appUrl' => $appUrl
            ),
            'times'    => 1,
            'duration' => 3600
        ));
        $url   = $this->generateUrl('common_parse_qrcode', array('token' => $token['token']), true);

        $response = array(
            'img' => $this->generateUrl('common_qrcode', array('text' => $url), true)
        );
        return $this->createJsonResponse($response);
    }

    public function taskActivityAction(Request $request, $courseId, $id)
    {
        $preview = $request->query->get('preview', 0);
        $task    = $this->tryLearnTask($courseId, $id, $preview);

        if (empty($preview) && $task['status'] != 'published') {
            return $this->render('task/inform.html.twig');
        }
        return $this->forward('AppBundle:Activity/Activity:show', array(
            'task'    => $task,
            'preview' => $preview
        ));
    }

    public function taskPluginsAction(Request $request, $courseId, $taskId)
    {
        $preview = $request->query->get('preview', false);

        $this->tryLearnTask($courseId, $taskId);

        return $this->createJsonResponse(array(
            array(
                'code' => 'task-list',
                'name' => '课程',
                'icon' => 'es-icon-menu',
                'url'  => $this->generateUrl('course_task_show_plugin_task_list', array(
                    'courseId' => $courseId,
                    'taskId'   => $taskId,
                    'preview'  => $preview
                ))
            ),
            array(
                'code' => 'note',
                'name' => '笔记',
                'icon' => 'es-icon-edit',
                'url'  => $this->generateUrl('course_task_plugin_note', array(
                    'courseId' => $courseId,
                    'taskId'   => $taskId
                ))
            ),
            array(
                'code' => 'question',
                'name' => '问答',
                'icon' => 'es-icon-help',
                'url'  => $this->generateUrl('course_task_plugin_threads', array(
                    'courseId' => $courseId,
                    'taskId'   => $taskId
                ))
            )
        ));
    }

    public function triggerAction(Request $request, $courseId, $id)
    {
        $this->getCourseService()->tryTakeCourse($courseId);

        $eventName      = 'doing';
        $data           = $request->request->get('data', array());
        $data['taskId'] = $id;
        $result         = $this->getTaskService()->trigger($id, $eventName, $data);
        return $this->createJsonResponse(array(
            'result'   => $result,
            'lastTime' => time(),
            'event'    => $eventName,
            'data'     => $data
        ));
    }

    public function finishAction($courseId, $id)
    {
        $course = $this->getCourseService()->getCourse($courseId);

        if (!$course['enableFinish']) {
            throw $this->createAccessDeniedException('task can not finished.');
        }

        $task = $this->getTaskService()->getTask($id);

        if ($task['status'] != 'published') {
            return $this->createMessageResponse('error', '未发布的任务无法完成');
        }
        $result = $this->getTaskService()->finishTaskResult($id);

        list($course, $nextTask, $finishedRate) = $this->getNextTaskAndFinishedRate($task);

        return $this->render('task/finish-result.html.twig', array(
            'result'       => $result,
            'task'         => $task,
            'nextTask'     => $nextTask,
            'course'       => $course,
            'finishedRate' => $finishedRate
        ));
    }

    public function taskFinishedPromptAction($courseId, $id)
    {
        $this->getCourseService()->tryTakeCourse($courseId);
        $result = $this->getTaskService()->finishTaskResult($id);
        $task   = $this->getTaskService()->getTask($id);

        list($course, $nextTask, $finishedRate) = $this->getNextTaskAndFinishedRate($task);

        return $this->render('task/task-finished-prompt.html.twig', array(
            'result'       => $result,
            'task'         => $task,
            'nextTask'     => $nextTask,
            'course'       => $course,
            'finishedRate' => $finishedRate
        ));
    }

    public function finishConditionAction($task)
    {
        $config   = $this->getActivityConfig();
        $action   = $config[$task['type']]['actions']['finishCondition'];
        $activity = $this->getActivityService()->getActivity($task['activityId']);
        return $this->forward($action, array('activity' => $activity));
    }

    protected function getNextTaskAndFinishedRate($task)
    {
        $nextTask      = $this->getTaskService()->getNextTask($task['id']);
        $course        = $this->getCourseService()->getCourse($task['courseId']);
        $user          = $this->getUser();
        $conditions    = array(
            'courseId' => $task['courseId'],
            'userId'   => $user['id'],
            'status'   => 'finish'
        );
        $finishedCount = $this->getTaskResultService()->countTaskResults($conditions);

        $finishedRate = empty($course['publishedTaskNum']) ? 0 : intval($finishedCount / $course['publishedTaskNum'] * 100);
        return array($course, $nextTask, $finishedRate);
    }

    protected function tryLearnTask($courseId, $taskId, $preview = false)
    {
        if ($preview) {
            if ($this->canPreviewCourse($courseId)) {
                $task = $this->getTaskService()->getTask($taskId);
            } else {
                throw $this->createNotFoundException('you can not preview this task ');
            }
        } else {
            $task = $this->getTaskService()->tryTakeTask($taskId);
        }
        if (empty($task)) {
            throw $this->createResourceNotFoundException('task', $taskId);
        }

        if ($task['courseId'] != $courseId) {
            throw $this->createAccessDeniedException();
        }

        return $task;
    }

    private function canPreviewCourse($courseId)
    {
        $user   = $this->getCurrentUser();
        $member = $this->getCourseMemberService()->getCourseMember($courseId, $user['id']);

        if (empty($member)) {
            return false;
        }

        $course    = $this->getCourseService()->getCourse($courseId);
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);

        if ($user->isSuperAdmin()) {
            return true;
        } elseif ($user['id'] == $courseSet['creator']) {
            return true;
        } elseif (in_array($user->getId(), $course['teacherIds'])) {
            return true;
        } elseif ($member['role'] == 'teacher') {
            return true;
        }
        return false;
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return TaskResultService
     */
    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    /**
     * @return TokenService
     */
    protected function getTokenService()
    {
        return $this->createService('User:TokenService');
    }

    protected function getActivityConfig()
    {
        return $this->get('extension.default')->getActivities();
    }
}
