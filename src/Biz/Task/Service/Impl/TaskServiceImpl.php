<?php

namespace Biz\Task\Service\Impl;

use Biz\BaseService;
use Biz\Task\Dao\TaskDao;
use Biz\Task\Service\TaskService;
use AppBundle\Common\ArrayToolkit;
use Biz\Course\Service\CourseService;
use Biz\Task\Strategy\CourseStrategy;
use Biz\Task\Strategy\StrategyContext;
use Biz\Task\Service\TaskResultService;
use Codeages\Biz\Framework\Event\Event;
use Biz\Course\Service\CourseSetService;
use Biz\Activity\Service\ActivityService;

class TaskServiceImpl extends BaseService implements TaskService
{
    public function getTask($id)
    {
        return $this->getTaskDao()->get($id);
    }

    public function getCourseTask($courseId, $id)
    {
        $task = $this->getTaskDao()->get($id);
        if (empty($task) || $task['courseId'] != $courseId) {
            return array();
        }

        return $task;
    }

    public function createTask($fields)
    {
        $fields = array_filter(
            $fields,
            function ($value) {
                if (is_array($value) || ctype_digit((string) $value)) {
                    return true;
                }

                return !empty($value);
            }
        );

        if ($this->invalidTask($fields)) {
            throw $this->createInvalidArgumentException('task is invalid');
        }

        if (!$this->getCourseService()->tryManageCourse($fields['fromCourseId'])) {
            throw $this->createAccessDeniedException('无权创建任务');
        }

        $this->beginTransaction();
        try {
            if (isset($fields['content'])) {
                $fields['content'] = $this->purifyHtml($fields['content'], true);
            }

            $fields = $this->createActivity($fields);
            $strategy = $this->createCourseStrategy($fields['courseId']);
            $task = $strategy->createTask($fields);
            $this->getLogService()->info('course', 'add_task', "添加任务《{$task['title']}》({$task['id']})", $task);
            $this->dispatchEvent('course.task.create', new Event($task));
            $this->commit();

            return $task;
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    protected function createActivity($fields)
    {
        $activity = $this->getActivityService()->createActivity($fields);

        $fields['activityId'] = $activity['id'];
        $fields['createdUserId'] = $activity['fromUserId'];
        $fields['courseId'] = $activity['fromCourseId'];
        $fields['seq'] = $this->getCourseService()->getNextCourseItemSeq($activity['fromCourseId']);
        $fields['type'] = $fields['mediaType'];
        $fields['endTime'] = $activity['endTime'];

        if ($activity['mediaType'] == 'video') {
            $fields['mediaSource'] = $fields['ext']['mediaSource'];
        }

        return $fields;
    }

    protected function invalidTask($task)
    {
        if (!ArrayToolkit::requireds($task, array('title', 'fromCourseId'))) {
            return true;
        }

        return false;
    }

    public function updateTask($id, $fields)
    {
        $task = $this->getTask($id);

        if (!$this->getCourseService()->tryManageCourse($task['courseId'])) {
            throw $this->createAccessDeniedException("can not update task #{$id}.");
        }

        $this->beginTransaction();
        try {
            $activity = $this->getActivityService()->updateActivity($task['activityId'], $fields);

            if ($activity['mediaType'] == 'video') {
                $fields['mediaSource'] = $fields['ext']['mediaSource'];
            }

            $fields['endTime'] = $activity['endTime'];
            $strategy = $this->createCourseStrategy($task['courseId']);
            $task = $strategy->updateTask($id, $fields);
            $this->getLogService()->info('course', 'update_task', "更新任务《{$task['title']}》({$task['id']})");
            $this->dispatchEvent('course.task.update', new Event($task), $fields);
            $this->commit();

            return $task;
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function publishTask($id)
    {
        $task = $this->getTask($id);

        if (!$this->getCourseService()->tryManageCourse($task['courseId'])) {
            throw $this->createAccessDeniedExcpubeption("can not publish task #{$id}.");
        }

        if ($task['status'] == 'published') {
            throw $this->createAccessDeniedException("task(#{$task['id']}) has been published");
        }

        $strategy = $this->createCourseStrategy($task['courseId']);

        $task = $strategy->publishTask($task);
        $this->dispatchEvent('course.task.publish', new Event($task));

        return $task;
    }

    public function publishTasksByCourseId($courseId)
    {
        $this->getCourseService()->tryManageCourse($courseId);
        $tasks = $this->findTasksByCourseId($courseId);
        if (!empty($tasks)) {
            foreach ($tasks as $task) {
                if ($task['status'] !== 'published') {
                    //mode存在且不等于lesson的任务会随着mode=lesson的任务发布，这里不应重复发布
                    if (!empty($task['mode']) && $task['mode'] !== 'lesson') {
                        continue;
                    }
                    $this->publishTask($task['id']);
                }
            }
        }
    }

    public function unpublishTask($id)
    {
        $task = $this->getTask($id);

        if (!$this->getCourseService()->tryManageCourse($task['courseId'])) {
            throw $this->createAccessDeniedException("can not unpublish task #{$id}.");
        }

        if ($task['status'] == 'unpublished') {
            throw $this->createAccessDeniedException("task(#{$task['id']}) has been unpublished");
        }

        $strategy = $this->createCourseStrategy($task['courseId']);
        $task = $strategy->unpublishTask($task);
        $this->dispatchEvent('course.task.unpublish', new Event($task));

        return $task;
    }

    public function updateSeq($id, $fields)
    {
        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'seq',
                'categoryId',
                'number',
            )
        );
        $task = $this->getTaskDao()->update($id, $fields);
        $this->dispatchEvent('course.task.update', new Event($task));

        return $task;
    }

    public function updateTasks($ids, $fields)
    {
        $fields = ArrayToolkit::parts($fields, array('isFree'));

        foreach ($ids as $id) {
            $_task = $this->getTaskDao()->update($id, $fields);
            //xxx 这里可能影响执行效率：1. 批量处理，2. 仅仅是更新isFree，却会触发task的所有信息
            $this->dispatchEvent('course.task.update', new Event($_task));
        }

        return true;
    }

    public function deleteTask($id)
    {
        $task = $this->getTask($id);
        if (!$this->getCourseService()->tryManageCourse($task['courseId'])) {
            throw $this->createAccessDeniedException('无权删除任务');
        }

        $result = $this->createCourseStrategy($task['courseId'])->deleteTask($task);

        $this->getLogService()->info('course', 'delete_task', "删除任务《{$task['title']}》({$task['id']})", $task);
        $this->dispatchEvent('course.task.delete', new Event($task, array('user' => $this->getCurrentUser())));

        return $result;
    }

    public function findTasksByCourseId($courseId)
    {
        return $this->getTaskDao()->findByCourseId($courseId);
    }

    public function findTasksByCourseIds($courseIds)
    {
        return $this->getTaskDao()->findByCourseIds($courseIds);
    }

    public function findTasksByActivityIds($activityIds)
    {
        $tasks = $this->getTaskDao()->findByActivityIds($activityIds);

        return ArrayToolkit::index($tasks, 'activityId');
    }

    public function countTasksByCourseId($courseId)
    {
        return $this->getTaskDao()->count(array('courseId' => $courseId));
    }

    public function findTasksByIds(array $ids)
    {
        return $this->getTaskDao()->findByIds($ids);
    }

    public function findTasksFetchActivityByCourseId($courseId)
    {
        $tasks = $this->findTasksByCourseId($courseId);
        $activityIds = ArrayToolkit::column($tasks, 'activityId');
        $activities = $this->getActivityService()->findActivities($activityIds, true);
        $activities = ArrayToolkit::index($activities, 'id');

        array_walk(
            $tasks,
            function (&$task) use ($activities) {
                $activity = $activities[$task['activityId']];
                $task['activity'] = $activity;
            }
        );

        return $tasks;
    }

    public function findTasksFetchActivityAndResultByCourseId($courseId)
    {
        $tasks = $this->findTasksFetchActivityByCourseId($courseId);
        if (empty($tasks)) {
            return array();
        }

        $taskResults = $this->getTaskResultService()->findUserTaskResultsByCourseId($courseId);
        $taskResults = ArrayToolkit::index($taskResults, 'courseTaskId');

        array_walk(
            $tasks,
            function (&$task) use ($taskResults) {
                $task['result'] = isset($taskResults[$task['id']]) ? $taskResults[$task['id']] : null;
            }
        );
        $isLock = false;
        foreach ($tasks as &$task) {
            $task = $this->setTaskLockStatus($tasks, $task);
            //设置第一个发布的任务为解锁的
            if ($task['status'] == 'published' && !$isLock) {
                $task['lock'] = false;
                $isLock = true;
            }
        }

        return $tasks;
    }

    protected function getPreTask($tasks, $currentTask)
    {
        return array_filter(
            array_reverse($tasks),
            function ($task) use ($currentTask) {
                return $currentTask['seq'] > $task['seq'];
            }
        );
    }

    /**
     * 给定一个任务 ，判断前置解锁条件是完成.
     *
     * @param  $preTasks
     *
     * @return bool
     */
    public function isPreTasksIsFinished($preTasks)
    {
        $continue = true;
        $canLearnTask = false;
        foreach (array_values($preTasks) as $key => $preTask) {
            if (empty($continue)) {
                break;
            }
            if ($preTask['status'] != 'published') {
                continue;
            }
            if ($preTask['isOptional']) {
                $canLearnTask = true;
            } elseif ($preTask['type'] == 'live') {
                $live = $this->getActivityService()->getActivity($preTask['activityId'], true);
                if (time() > $live['endTime']) {
                    $canLearnTask = true;
                } else {
                    $isTaskLearned = empty($preTask['result']) ? ($preTask['status'] == 'finish') : false;
                    if ($isTaskLearned) {
                        $canLearnTask = true;
                    } else {
                        $canLearnTask = false;
                        $continue = false;
                    }
                }
            } elseif ($preTask['type'] == 'testpaper' && $preTask['startTime']) {
                $testPaper = $this->getActivityService()->getActivity($preTask['activityId'], true);
                if (time() > $preTask['startTime'] + $testPaper['ext']['limitedTime'] * 60) {
                    $canLearnTask = true;
                } else {
                    $isTaskLearned = empty($preTask['result']) ? ($preTask['status'] == 'finish') : false;
                    if ($isTaskLearned) {
                        $canLearnTask = true;
                    } else {
                        $canLearnTask = false;
                        $continue = false;
                    }
                }
            } else {
                $isTaskLearned = empty($preTask['result']) ? ($preTask['status'] == 'finish') : false;
                if ($isTaskLearned) {
                    $canLearnTask = true;
                } else {
                    $canLearnTask = false;
                }
                $continue = false;
            }

            if ((count($preTasks) - 1) == $key) {
                break;
            }
        }

        return $canLearnTask;
    }

    public function findUserTeachCoursesTasksByCourseSetId($userId, $courseSetId)
    {
        $conditions = array(
            'userId' => $userId,
        );
        $myTeachCourses = $this->getCourseService()->findUserTeachCourses($conditions, 0, PHP_INT_MAX, true);

        $conditions = array(
            'courseIds' => ArrayToolkit::column($myTeachCourses, 'courseId'),
            'courseSetId' => $courseSetId,
        );
        $courses = $this->getCourseService()->searchCourses(
            $conditions,
            array('createdTime' => 'DESC'),
            0,
            PHP_INT_MAX
        );

        return $this->findTasksByCourseIds(ArrayToolkit::column($courses, 'id'));
    }

    public function searchTasks($conditions, $orderBy, $start, $limit)
    {
        return $this->getTaskDao()->search($conditions, $orderBy, $start, $limit);
    }

    public function countTasks($conditions)
    {
        return $this->getTaskDao()->count($conditions);
    }

    public function startTask($taskId)
    {
        $task = $this->getTask($taskId);

        $user = $this->getCurrentUser();

        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($task['id']);

        if (!empty($taskResult)) {
            return;
        }

        $taskResult = array(
            'activityId' => $task['activityId'],
            'courseId' => $task['courseId'],
            'courseTaskId' => $task['id'],
            'userId' => $user['id'],
        );

        $taskResult = $this->getTaskResultService()->createTaskResult($taskResult);

        $this->dispatchEvent('course.task.start', new Event($taskResult));
    }

    public function doTask($taskId, $time = TaskService::LEARN_TIME_STEP)
    {
        $task = $this->tryTakeTask($taskId);

        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($task['id']);

        if (empty($taskResult)) {
            throw $this->createAccessDeniedException('task #{taskId} can not do. ');
        }

        $this->getTaskResultService()->waveLearnTime($taskResult['id'], $time);
    }

    public function watchTask($taskId, $watchTime = TaskService::WATCH_TIME_STEP)
    {
        $task = $this->tryTakeTask($taskId);

        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($task['id']);

        if (empty($taskResult)) {
            throw $this->createAccessDeniedException('task #{taskId} can not do. ');
        }

        $this->getTaskResultService()->waveWatchTime($taskResult['id'], $watchTime);
    }

    public function finishTask($taskId)
    {
        $this->tryTakeTask($taskId);

        if (!$this->isFinished($taskId)) {
            throw $this->createAccessDeniedException(
                "can not finish task #{
        $taskId}."
            );
        }

        return $this->finishTaskResult($taskId);
    }

    public function finishTaskResult($taskId)
    {
        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($taskId);

        if (empty($taskResult)) {
            $task = $this->getTask($taskId);
            $activity = $this->getActivityService()->getActivity($task['activityId']);
            if ($activity['mediaType'] == 'live') {
                $this->trigger($activity['id'], 'start', array('task' => $task));
                $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($taskId);
            } else {
                throw $this->createAccessDeniedException('task access denied. ');
            }
        }

        if ($taskResult['status'] === 'finish') {
            return $taskResult;
        }

        $update['updatedTime'] = time();
        $update['status'] = 'finish';
        $update['finishedTime'] = time();
        $taskResult = $this->getTaskResultService()->updateTaskResult($taskResult['id'], $update);
        $this->dispatchEvent('course.task.finish', new Event($taskResult, array('user' => $this->getCurrentUser())));

        return $taskResult;
    }

    public function findFreeTasksByCourseId($courseId)
    {
        $tasks = $this->getTaskDao()->findByCourseIdAndIsFree($courseId, $isFree = true);
        $tasks = ArrayToolkit::index($tasks, 'id');

        return $tasks;
    }

    /**
     * 设置当前任务最大可同时进行的人数  如直播任务等.
     *
     * @param  $taskId
     * @param  $maxNum
     *
     * @return mixed
     */
    public function setTaskMaxOnlineNum($taskId, $maxNum)
    {
        return $this->getTaskDao()->update($taskId, array('maxOnlineNum' => $maxNum));
    }

    /**
     * 统计当前时间以后每天的直播次数.
     *
     * @param  $limit
     *
     * @return array <string, int|string>
     */
    public function findFutureLiveDates($limit = 4)
    {
        return $this->getTaskDao()->findFutureLiveDates($limit);
    }

    public function findPublishedLivingTasksByCourseSetId($courseSetId)
    {
        $conditions = array(
            'fromCourseSetId' => $courseSetId,
            'type' => 'live',
            'status' => 'published',
            'startTime_LT' => time(),
            'endTime_GT' => time(),
        );

        return $this->searchTasks($conditions, array('startTime' => 'ASC'), 0, $this->countTasks($conditions));
    }

    public function findPublishedTasksByCourseSetId($courseSetId)
    {
        $conditions = array(
            'fromCourseSetId' => $courseSetId,
            'type' => 'live',
            'status' => 'published',
        );

        return $this->searchTasks($conditions, array('startTime' => 'ASC'), 0, $this->countTasks($conditions));
    }

    /**
     * 返回当前正在直播的直播任务
     *
     * @return array
     */
    public function findCurrentLiveTasks()
    {
        $condition = array(
            'startTime_LE' => time(),
            'endTime_GT' => time(),
            'type' => 'live',
            'status' => 'published',
        );

        return $this->searchTasks($condition, array('startTime' => 'ASC'), 0, $this->countTasks($condition));
    }

    /**
     * 返回当前将要直播的直播任务
     *
     * @return array
     */
    public function findFutureLiveTasks()
    {
        $condition = array(
            'startTime_GT' => time(),
            'endTime_LT' => strtotime(date('Y-m-d').' 23:59:59'),
            'type' => 'live',
            'status' => 'published',
        );

        return $this->searchTasks($condition, array('startTime' => 'ASC'), 0, $this->countTasks($condition));
    }

    /**
     * 返回过去直播过的教学计划ID.
     *
     * @return array
     */
    public function findPastLivedCourseSetIds()
    {
        $arrays = $this->getTaskDao()->findPastLivedCourseSetIds();

        return ArrayToolkit::column($arrays, 'fromCourseSetId');
    }

    public function isFinished($taskId)
    {
        $task = $this->getTask($taskId);

        return $this->getActivityService()->isFinished($task['activityId']);
    }

    public function tryTakeTask($taskId)
    {
        if (!$this->canLearnTask($taskId)) {
            throw $this->createAccessDeniedException('the Task is Locked');
        }
        $task = $this->getTask($taskId);

        if (empty($task)) {
            throw $this->createNotFoundException('task does not exist');
        }

        return $task;
    }

    public function getNextTask($taskId)
    {
        $task = $this->getTask($taskId);

        //取得下一个发布的课时
        $conditions = array(
            'courseId' => $task['courseId'],
            'status' => 'published',
            'seq_GT' => $task['seq'],
        );
        $nextTasks = $this->getTaskDao()->search($conditions, array('seq' => 'ASC'), 0, 1);
        if (empty($nextTasks)) {
            return array();
        }
        $nextTask = array_shift($nextTasks);

        //判断下一个课时是否课时学习
        if (!$this->canLearnTask($nextTask['id'])) {
            return array();
        }

        return $nextTask;
    }

    public function canLearnTask($taskId)
    {
        $task = $this->getTask($taskId);

        $this->getCourseService()->tryTakeCourse($task['courseId']);

        //check if has permission to course and task
        $isAllowed = false;
        if ($task['isFree']) {
            $isAllowed = true;
        } elseif ($this->getCourseService()->canTakeCourse($task['courseId'])) {
            $isAllowed = true;
        }

        if ($isAllowed) {
            return $this->createCourseStrategy($task['courseId'])->canLearnTask($task);
        }

        return false;
    }

    public function isTaskLearned($taskId)
    {
        $taskResult = $this->getTaskResultService()->getUserTaskResultByTaskId($taskId);

        return empty($taskResult) ? false : ('finish' == $taskResult['status']);
    }

    public function getMaxSeqByCourseId($courseId)
    {
        return $this->getTaskDao()->getMaxSeqByCourseId($courseId);
    }

    public function getMaxNumberByCourseId($courseId)
    {
        return $this->getTaskDao()->getNumberSeqByCourseId($courseId);
    }

    public function getTaskByCourseIdAndActivityId($courseId, $activityId)
    {
        return $this->getTaskDao()->getTaskByCourseIdAndActivityId($courseId, $activityId);
    }

    public function findTasksByChapterId($chapterId)
    {
        return $this->getTaskDao()->findByChapterId($chapterId);
    }

    public function findTasksFetchActivityByChapterId($chapterId)
    {
        $tasks = $this->findTasksByChapterId($chapterId);

        $activityIds = ArrayToolkit::column($tasks, 'activityId');
        $activities = $this->getActivityService()->findActivities($activityIds);
        $activities = ArrayToolkit::index($activities, 'id');

        array_walk(
            $tasks,
            function (&$task) use ($activities) {
                $activity = $activities[$task['activityId']];
                $task['activity'] = $activity;
            }
        );

        return $tasks;
    }

    /**
     * @param  $courseId
     *
     * @return array tasks
     */
    public function findToLearnTasksByCourseId($courseId)
    {
        list($course) = $this->getCourseService()->tryTakeCourse($courseId);
        $toLearnTasks = $tasks = array();

        if (!in_array($course['learnMode'], array('freeMode', 'lockMode'))) {
            return $toLearnTasks;
        }

        if ($course['learnMode'] == 'freeMode') {
            $toLearnTask = $this->getToLearnTaskWithFreeMode($courseId);
            if (!empty($toLearnTask)) {
                $toLearnTasks[] = $toLearnTask;
            }
        }
        if ($course['learnMode'] == 'lockMode') {
            list($tasks, $toLearnTasks) = $this->getToLearnTasksWithLockMode($courseId);
        }

        $toLearnTasks = $this->fillTaskResultAndLockStatus($toLearnTasks, $course, $tasks);

        return $toLearnTasks;
    }

    /**
     * @param  $courseId
     *
     * @return array|mixed
     */
    public function findToLearnTasksByCourseIdForMission($courseId)
    {
        list($course) = $this->getCourseService()->tryTakeCourse($courseId);
        $toLearnTasks = $tasks = array();

        if (!in_array($course['learnMode'], array('freeMode', 'lockMode'))) {
            return $toLearnTasks;
        }
        list($tasks, $toLearnTasks) = $this->getToLearnTasksWithLockMode($courseId);

        $toLearnTasks = $this->fillTaskResultAndLockStatus($toLearnTasks, $course, $tasks);

        return $toLearnTasks;
    }

    protected function getToLearnTaskWithFreeMode($courseId)
    {
        $taskResults = $this->getTaskResultService()->findUserProgressingTaskResultByCourseId($courseId);

        if (empty($taskResults)) {
            $tasks = $this->getTaskDao()->findByCourseId($courseId);
            $taskResults = $this->getTaskResultService()->findUserTaskResultsByCourseId($courseId);

            $finishedTaskIds = ArrayToolkit::column($taskResults, 'courseTaskId');

            $tasks = array_filter(
                $tasks,
                function ($task) use ($finishedTaskIds) {
                    return !in_array($task['id'], $finishedTaskIds);
                }
            );
            if (empty($tasks)) {
                //任务已全部完成
                return array();
            }
            $toLearnTask = array_shift($tasks);
        } else {
            $latestTaskResult = array_shift($taskResults);
            $latestLearnTask = $this->getTask($latestTaskResult['courseTaskId']); //获取最新学习未学完的课程
            $conditions = array(
                'seq_GE' => $latestLearnTask['seq'],
                'courseId' => $courseId,
                'status' => 'published',
            );

            $tasks = $this->getTaskDao()->search($conditions, array('seq' => 'ASC'), 0, 2);
            $toLearnTask = array_pop($tasks); //如果当正在学习的是最后一个，则取当前在学的任务
            if (empty($toLearnTask)) {
                $toLearnTask = $latestLearnTask;
            }
        }

        return $toLearnTask;
    }

    protected function getToLearnTasksWithLockMode($courseId)
    {
        $toLearnTaskCount = 3;
        $taskResult = $this->getTaskResultService()->getUserLatestFinishedTaskResultByCourseId($courseId);
        $toLearnTasks = array();

        //取出所有的任务
        $taskCount = $this->countTasksByCourseId($courseId);
        $tasks = $this->getTaskDao()->search(array('courseId' => $courseId), array('seq' => 'ASC'), 0, $taskCount);

        if (empty($taskResult)) {
            $toLearnTasks = $this->getTaskDao()->search(
                array('courseId' => $courseId, 'status' => 'published'),
                array('seq' => 'ASC'),
                0,
                $toLearnTaskCount
            );

            return array($tasks, $toLearnTasks);
        }

        if (count($tasks) <= $toLearnTaskCount) {
            $toLearnTasks = $tasks;

            return array($tasks, $toLearnTasks);
        }

        $previousTask = null;
        //向后取待学习的三个任务
        foreach ($tasks as $task) {
            if ($task['id'] == $taskResult['courseTaskId']) {
                $toLearnTasks[] = $task;
                $previousTask = $task;
            }
            if ($previousTask && $task['seq'] > $previousTask['seq'] && count($toLearnTasks) < $toLearnTaskCount) {
                array_push($toLearnTasks, $task);
                $previousTask = $task;
            }
        }

        //向后去待学习的任务不足3个，向前取。
        $reverseTasks = array_reverse($tasks);
        if (count($toLearnTasks) < $toLearnTaskCount) {
            foreach ($reverseTasks as $task) {
                if ($task['id'] == $taskResult['courseTaskId']) {
                    $previousTask = $task;
                }
                if ($previousTask && $task['seq'] < $previousTask['seq'] && count($toLearnTasks) < $toLearnTaskCount) {
                    array_unshift($toLearnTasks, $task);
                    $previousTask = $task;
                }
            }
        }

        return array($tasks, $toLearnTasks);
    }

    public function trigger($id, $eventName, $data = array())
    {
        $task = $this->getTask($id);
        $data['task'] = $task;
        $this->getActivityService()->trigger($task['activityId'], $eventName, $data);

        return $this->getTaskResultService()->getUserTaskResultByTaskId($id);
    }

    public function sumCourseSetLearnedTimeByCourseSetId($courseSetId)
    {
        return $this->getTaskDao()->sumCourseSetLearnedTimeByCourseSetId($courseSetId);
    }

    public function analysisTaskDataByTime($startTime, $endTime)
    {
        return $this->getTaskDao()->analysisTaskDataByTime($startTime, $endTime);
    }

    /**
     * 获取用户最近进行的一个任务
     *
     * @param int $userId
     *
     * @return array
     */
    public function getUserRecentlyStartTask($userId)
    {
        $results = $this->getTaskResultService()->searchTaskResults(
            array(
                'userId' => $userId,
            ),
            array(
                'createdTime' => 'DESC',
            ),
            0,
            1
        );
        $result = array_shift($results);
        if (empty($result)) {
            return array();
        }
        $task = $this->getTask($result['courseTaskId']);

        return $task;
    }

    /**
     * @return TaskDao
     */
    protected function getTaskDao()
    {
        return $this->createDao('Task:TaskDao');
    }

    /**
     * @param  $courseId
     *
     * @throws \Codeages\Biz\Framework\Service\Exception\NotFoundException
     *
     * @return CourseStrategy
     */
    protected function createCourseStrategy($courseId)
    {
        $course = $this->getCourseService()->getCourse($courseId);
        if (empty($course)) {
            throw $this->createNotFoundException('course does not exist');
        }

        return StrategyContext::getInstance()->createStrategy($course['isDefault'], $this->biz);
    }

    /**
     * @return ActivityService
     */
    protected function getActivityService()
    {
        return $this->biz->service('Activity:ActivityService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->biz->service('Course:CourseService');
    }

    /**
     * @return TaskResultService
     */
    protected function getTaskResultService()
    {
        return $this->biz->service('Task:TaskResultService');
    }

    protected function getCourseMemberService()
    {
        return $this->biz->service('Course:MemberService');
    }

    /**
     * @param  $tasks
     * @param  $task
     *
     * @return mixed
     */
    protected function setTaskLockStatus($tasks, $task)
    {
        try {
            $this->getCourseService()->tryManageCourse($task['courseId'], $task['fromCourseSetId']);
            $task['lock'] = false;
        } catch (\Exception $e) {
            $preTasks = $this->getPreTask($tasks, $task);

            if (empty($preTasks)) {
                $task['lock'] = false;
            }

            $finish = $this->isPreTasksIsFinished($preTasks);
            //当前任务未完成且前一个问题未完成则锁定
            $task['lock'] = !$finish;

            //选修任务不需要判断解锁条件
            if ($task['isOptional']) {
                $task['lock'] = false;
            }

            if ($task['type'] == 'live') {
                $task['lock'] = false;
            }

            if ($task['type'] == 'testpaper' && $task['startTime']) {
                $task['lock'] = false;
            }

            //如果该任务已经完成则忽略其他的条件
            if (isset($task['result']['status']) && ($task['result']['status'] == 'finish')) {
                $task['lock'] = false;
            }
        }

        return $task;
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    /**
     * @param  $toLearnTasks
     * @param  $course
     * @param  $tasks
     *
     * @return mixed
     */
    protected function fillTaskResultAndLockStatus($toLearnTasks, $course, $tasks)
    {
        $activityIds = ArrayToolkit::column($toLearnTasks, 'activityId');

        $activities = $this->getActivityService()->findActivities($activityIds);
        $activities = ArrayToolkit::index($activities, 'id');

        $taskIds = ArrayToolkit::column($toLearnTasks, 'id');
        $taskResults = $this->getTaskResultService()->findUserTaskResultsByTaskIds($taskIds);
        $taskResults = ArrayToolkit::index($taskResults, 'courseTaskId');

        array_walk(
            $tasks,
            function (&$task) use ($taskResults) {
                $task['result'] = isset($taskResults[$task['id']]) ? $taskResults[$task['id']] : null;
            }
        );

        //设置任务是否解锁
        foreach ($toLearnTasks as &$toLearnTask) {
            $toLearnTask['activity'] = $activities[$toLearnTask['activityId']];
            $toLearnTask['result'] = isset($taskResults[$toLearnTask['id']]) ? $taskResults[$toLearnTask['id']] : null;
            if ($course['learnMode'] == 'lockMode') {
                $toLearnTask = $this->setTaskLockStatus($tasks, $toLearnTask);
            }
        }

        return $toLearnTasks;
    }
}
