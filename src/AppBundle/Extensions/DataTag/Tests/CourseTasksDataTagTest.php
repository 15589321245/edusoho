<?php

namespace AppBundle\Extensions\DataTag\Test;

use Biz\BaseTestCase;

;
use Biz\Task\Service\TaskResultService;
use Biz\Task\Service\TaskService;
use AppBundle\Extensions\DataTag\CourseLessonsDataTag;

class CourseTasksDataTagTest extends BaseTestCase
{

    public function testGetData()
    {

        $course = array(
            'title' => 'online test course 1',
        );
        $course = $this->getCourseService()->createCourse($course);

        $task = array(
            'courseId' => $course['id'],
            'title'    => 'test lesson 1',
            'content'  => 'test lesson content 1',
            'type'     => 'text'
        );
        $lesson = $this->getTaskService()->createTask($task);

        $datatag = new CourseLessonsDataTag();
        $lessons = $datatag->getData(array('courseId' => $course['id']));

        $this->assertEquals(1, count($lessons));

        $foundLesson = array_pop($lessons);
        $this->assertEquals($lesson['id'], $foundLesson['id']);

    }

    private function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course:CourseService');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->getServiceKernel()->createService('Task:TaskService');
    }


}