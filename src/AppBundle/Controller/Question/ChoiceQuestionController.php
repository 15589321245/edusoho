<?php

namespace AppBundle\Controller\Question;

use AppBundle\Controller\BaseController;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\HttpFoundation\Request;

class ChoiceQuestionController extends BaseController
{
    public function showAction(Request $request, $id, $courseId)
    {
        // TODO: Implement showAction() method.
    }

    public function editAction(Request $request, $courseSetId, $questionId)
    {
        $user = $this->getUser();
        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);
        $courseTasks = $this->getCourseTaskService()->findUserTeachCoursesTasksByCourseSetId($user['id'], $courseSet['id']);
        $question = $this->getQuestionService()->get($questionId);

        $parentQuestion = array();
        if ($question['parentId'] > 0) {
            $parentQuestion = $this->getQuestionService()->get($question['parentId']);
        }

        return $this->render('question-manage/choice-form.html.twig', array(
            'courseSet' => $courseSet,
            'question' => $question,
            'parentQuestion' => $parentQuestion,
            'type' => $question['type'],
            'courseTasks' => $courseTasks,
        ));
    }

    public function createAction(Request $request, $courseSetId, $type)
    {
        $user = $this->getUser();
        $courseSet = $this->getCourseSetService()->getCourseSet($courseSetId);
        $courseTasks = $this->getCourseTaskService()->findUserTeachCoursesTasksByCourseSetId($user['id'], $courseSet['id']);

        $parentId = $request->query->get('parentId', 0);
        $parentQuestion = $this->getQuestionService()->get($parentId);

        return $this->render('question-manage/choice-form.html.twig', array(
            'courseSet' => $courseSet,
            'parentQuestion' => $parentQuestion,
            'courseTasks' => $courseTasks,
            'type' => $type,
        ));
    }

    protected function getQuestionService()
    {
        return $this->createService('Question:QuestionService');
    }

    protected function getCourseTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getServiceKernel()
    {
        return ServiceKernel::instance();
    }
}
