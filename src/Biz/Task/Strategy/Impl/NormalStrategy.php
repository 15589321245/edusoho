<?php

namespace Biz\Task\Strategy\Impl;

use AppBundle\Common\ArrayToolkit;
use Biz\Task\Strategy\BaseStrategy;
use Biz\Task\Strategy\CourseStrategy;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;

class NormalStrategy extends BaseStrategy implements CourseStrategy
{
    public function createTask($field)
    {
        $task = parent::createTask($field);

        $task['activity'] = $this->getActivityService()->getActivity($task['activityId'], $fetchMedia = true);

        return $task;
    }

    public function getTasksTemplate()
    {
        return 'course-manage/tasks/normal-tasks.html.twig';
    }

    public function getTaskItemTemplate()
    {
        return 'task-manage/item/normal-list-item.html.twig';
    }

    public function deleteTask($task)
    {
        if (empty($task)) {
            return true;
        }

        try {
            $this->biz['db']->beginTransaction();

            $this->getTaskDao()->delete($task['id']);
            $this->getTaskResultService()->deleteUserTaskResultByTaskId($task['id']);
            $this->getActivityService()->deleteActivity($task['activityId']);

            $this->biz['db']->commit();
        } catch (\Exception $e) {
            $this->biz['db']->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 任务学习.
     *
     * @param  $task
     *
     * @throws NotFoundException
     *
     * @return bool
     */
    public function canLearnTask($task)
    {
        $course = $this->getCourseService()->getCourse($task['courseId']);

        //自由式学习 可以学习任意课时
        if ($course['learnMode'] == 'freeMode') {
            return true;
        }

        //选修任务不需要判断解锁条件
        if ($task['isOptional']) {
            return true;
        }

        if ($task['type'] == 'live') {
            return true;
        }

        if ($task['type'] == 'testpaper' && $task['startTime']) {
            return true;
        }

        //取得下一个发布的课时
        $conditions = array(
            'courseId' => $task['courseId'],
            'seq_LT' => $task['seq'],
            'status' => 'published',
        );

        $count = $this->getTaskDao()->count($conditions);
        $preTasks = $this->getTaskDao()->search($conditions, array('seq' => 'DESC'), 0, $count);

        if (empty($preTasks)) {
            return true;
        }

        $taskIds = ArrayToolkit::column($preTasks, 'id');

        $taskResults = $this->getTaskResultService()->findUserTaskResultsByTaskIds($taskIds);
        $taskResults = ArrayToolkit::index($taskResults, 'courseTaskId');
        array_walk(
            $preTasks,
            function (&$task) use ($taskResults) {
                $task['result'] = isset($taskResults[$task['id']]) ? $taskResults[$task['id']] : null;
            }
        );

        return $this->getTaskService()->isPreTasksIsFinished($preTasks);
    }

    public function prepareCourseItems($courseId, $tasks, $limitNum)
    {
        $items = array();
        foreach ($tasks as $task) {
            $task['itemType'] = 'task';
            $items["task-{$task['id']}"] = $task;
        }

        $chapters = $this->getChapterDao()->findChaptersByCourseId($courseId);
        foreach ($chapters as $index => $chapter) {
            $chapter['itemType'] = 'chapter';
            $items["chapter-{$chapter['id']}"] = $chapter;
        }

        uasort(
            $items,
            function ($item1, $item2) {
                return $item1['seq'] > $item2['seq'];
            }
        );

        if (empty($limitNum)) {
            return $items;
        }

        $taskCount = 0;
        foreach ($items as $key => $item) {
            if (strpos($key, 'task') !== false) {
                ++$taskCount;
            }
            if ($taskCount > $limitNum) {
                unset($items[$key]);
            }
        }

        return $items;
    }

    public function sortCourseItems($courseId, array $itemIds)
    {
        if (empty($itemIds)) {
            return;
        }

        $parentChapters = array(
            'lesson' => array(),
            'unit' => array(),
            'chapter' => array(),
        );
        $taskNumber = 0;
        $chapterTypes = array('chapter' => 3, 'unit' => 2, 'lesson' => 1);
        foreach ($itemIds as $key => $id) {
            if (strpos($id, 'chapter') === 0) {
                $id = str_replace('chapter-', '', $id);
                $chapter = $this->getChapterDao()->get($id);
                $fields = array('seq' => $key, 'parentId' => 0);

                $index = $chapterTypes[$chapter['type']];
                switch ($index) {
                    case 3:
                        $fields['parentId'] = 0;
                        break;
                    case 2:
                        if (!empty($parentChapters['chapter'])) {
                            $fields['parentId'] = $parentChapters['chapter']['id'];
                        }
                        break;
                    case 1:
                        if (!empty($parentChapters['unit'])) {
                            $fields['parentId'] = $parentChapters['unit']['id'];
                        } elseif (!empty($parentChapters['chapter'])) {
                            $fields['parentId'] = $parentChapters['chapter']['id'];
                        }
                        break;
                    default:
                        break;
                }

                if (!empty($parentChapters[$chapter['type']])) {
                    $fields['number'] = $parentChapters[$chapter['type']]['number'] + 1;
                } else {
                    $fields['number'] = 1;
                }

                foreach ($chapterTypes as $type => $value) {
                    if ($value < $index) {
                        $parentChapters[$type] = array();
                    }
                }

                $chapter = $this->getCourseService()->updateChapter($courseId, $id, $fields);

                $parentChapters[$chapter['type']] = $chapter;
            }

            if (strpos($id, 'task') === 0) {
                $categoryId = empty($chapter) ? 0 : $chapter['id'];
                $id = str_replace('task-', '', $id);
                $task = $this->getTaskService()->getTask($id);
                if ($task['isOptional']) {
                    $number = 0;
                } else {
                    ++$taskNumber;
                    $number = $taskNumber;
                }

                $this->getTaskService()->updateSeq(
                    $id,
                    array(
                        'seq' => $key,
                        'categoryId' => $categoryId,
                        'number' => $number,
                    )
                );
            }
        }
    }

    public function publishTask($task)
    {
        return $this->getTaskDao()->update($task['id'], array('status' => 'published'));
    }

    public function unpublishTask($task)
    {
        return $this->getTaskDao()->update($task['id'], array('status' => 'unpublished'));
    }
}
