<?php

namespace AppBundle\Controller\My;

use AppBundle\Common\Paginator;
use Biz\Classroom\Service\ClassroomService;
use Biz\Task\Service\TaskService;
use Biz\Course\Service\CourseService;
use Biz\Task\Service\TaskResultService;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\Course\CourseBaseController;

class CourseController extends CourseBaseController
{
    public function indexAction()
    {
        if ($this->getCurrentUser()->isTeacher()) {
            return $this->redirect($this->generateUrl('my_teaching_course_sets'));
        } else {
            return $this->redirect($this->generateUrl('my_courses_learning'));
        }
    }

    public function learningAction(Request $request)
    {
        $currentUser = $this->getUser();
        $paginator = new Paginator(
            $request,
            $this->getCourseService()->countUserLearningCourses($currentUser['id']),
            12
        );

        $courses = $this->getCourseService()->findUserLearningCourses(
            $currentUser['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render(
            'my/learning/course/learning.html.twig',
            array(
                'courses' => $courses,
                'paginator' => $paginator,
            )
        );
    }

    public function learnedAction()
    {
        $currentUser = $this->getCurrentUser();
        $paginator = new Paginator(
            $this->get('request'),
            $this->getCourseService()->countUserLearnedCourses($currentUser['id']),
            12
        );

        $courses = $this->getCourseService()->findUserLearnedCourses(
            $currentUser['id'],
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $userIds = array();
        foreach ($courses as $key => $course) {
            $userIds = array_merge($userIds, $course['teacherIds']);
            $learnTime = $this->getTaskResultService()->sumLearnTimeByCourseIdAndUserId(
                $course['id'],
                $currentUser['id']
            );

            $courses[$key]['learnTime'] = intval($learnTime / 60).'小时'.($learnTime % 60).'分钟';
        }
        $users = $this->getUserService()->findUsersByIds($userIds);

        return $this->render(
            'my/learning/course/learned.html.twig',
            array(
                'courses' => $courses,
                'users' => $users,
                'paginator' => $paginator,
            )
        );
    }

    public function headerForMemberAction($course, $member)
    {
        $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
        $courses = $this->getCourseService()->findPublishedCoursesByCourseSetId($course['courseSetId']);

        $taskCount = $this->getTaskService()->countTasks(array('courseId' => $course['id'], 'status' => 'published'));
        $progress = $taskResultCount = $toLearnTasks = $taskPerDay = $planStudyTaskCount = $planProgressProgress = 0;

        $user = $this->getUser();
        if ($taskCount && empty($member['previewAs'])) {
            //学习记录
            $taskResultCount = $this->getTaskResultService()->countTaskResults(
                array('courseId' => $course['id'], 'status' => 'finish', 'userId' => $user['id'])
            );

            //学习进度
            $progress = empty($taskCount) ? 0 : round($taskResultCount / $taskCount, 2) * 100;
            $progress = $progress > 100 ? 100 : $progress;

            //待学习任务
            $toLearnTasks = $this->getTaskService()->findToLearnTasksByCourseId($course['id']);

            //任务式课程每日建议学习任务数
            $taskPerDay = $this->getFinishedTaskPerDay($course, $taskCount);

            //计划应学数量
            $planStudyTaskCount = $this->getPlanStudyTaskCount($course, $member, $taskCount, $taskPerDay);

            //计划进度
            $planProgressProgress = empty($taskCount) ? 0 : round($planStudyTaskCount / $taskCount, 2) * 100;

        }

        $isUserFavorite = false;
        if ($user->isLogin()) {
            $isUserFavorite = $this->getCourseSetService()->isUserFavorite($user['id'], $course['courseSetId']);
        }

        return $this->render(
            'course/header/header-for-member.html.twig',
            array(
                'courseSet' => $courseSet,
                'courses' => $courses,
                'course' => $course,
                'member' => $member,
                'progress' => $progress,
                'taskCount' => $taskCount,
                'taskResultCount' => $taskResultCount,
                'toLearnTasks' => $toLearnTasks,
                'taskPerDay' => $taskPerDay,
                'planStudyTaskCount' => $planStudyTaskCount,
                'planProgressProgress' => $planProgressProgress,
                'isUserFavorite' => $isUserFavorite,
                'marketingPage' => 0,
            )
        );
    }

    public function showAction(Request $request, $id, $tab = 'tasks')
    {
        $course = $this->getCourseService()->getCourse($id);
        $member = $this->getCourseMember($request, $course);

        if (empty($member)) {
            return $this->redirect(
                $this->generateUrl(
                    'course_show',
                    array(
                        'id' => $id,
                        'tab' => $tab,
                    )
                )
            );
        }

        $classroom = array();
        if ($course['parentId'] > 0) {
            $classroom = $this->getClassroomService()->getClassroomByCourseId($course['id']);
        }

        return $this->render(
            'course/course-show.html.twig',
            array(
                'tab' => $tab,
                'member' => $member,
                'isCourseTeacher' => $member['role'] == 'teacher',
                'course' => $course,
                'classroom' => $classroom,
            )
        );
    }

    /**
     * @return TaskResultService
     */
    public function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }

    protected function getFinishedTaskPerDay($course, $taskNum)
    {
        //自由式不需要展示每日计划的学习任务数
        if ($course['learnMode'] == 'freeMode') {
            return false;
        }
        if ($course['expiryMode'] == 'days') {
            $finishedTaskPerDay = empty($course['expiryDays']) ? false : $taskNum / $course['expiryDays'];
        } else {
            $diffDay = ($course['expiryEndDate'] - $course['expiryStartDate']) / (24 * 60 * 60);
            $finishedTaskPerDay = empty($diffDay) ? false : $taskNum / $diffDay;
        }

        return round($finishedTaskPerDay);
    }

    protected function getPlanStudyTaskCount($course, $member, $taskNum, $taskPerDay)
    {
        //自由式不需要展示应学任务数, 未设置学习有效期不需要展示应学任务数
        if ($course['learnMode'] == 'freeMode' || empty($taskPerDay)) {
            return false;
        }
        //当前时间减去课程
        //按天计算有效期， 当前的时间- 加入课程的时间 获得天数* 每天应学任务
        if ($course['expiryMode'] == 'days') {
            $joinDays = (time() - $member['createdTime']) / (24 * 60 * 60);
        } else {
            //当前时间-减去课程有效期开始时间  获得天数 *应学任务数量
            $joinDays = (time() - $course['expiryStartDate']) / (24 * 60 * 60);
        }

        return $taskPerDay * $joinDays >= $taskNum ? $taskNum : round($taskPerDay * $joinDays);
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    /**
     * @return ClassroomService
     */
    protected function getClassroomService()
    {
        return $this->createService('Classroom:ClassroomService');
    }
}
