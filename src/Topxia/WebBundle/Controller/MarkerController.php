<?php
namespace Topxia\WebBundle\Controller;

use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;

class MarkerController extends BaseController
{
    public function manageAction(Request $request, $courseId, $lessonId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);
        $lesson = $this->getCourseService()->getCourseLesson($courseId, $lessonId);

        $this->getMarkerService()->canManageMarker($lesson['userId']);

        return $this->render('TopxiaWebBundle:Marker:index.html.twig', array(
            'course' => $course,
            'lesson' => $lesson
        ));
    }

    //驻点合并
    public function mergeAction(Request $request, $courseId, $lessonId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);

        $data = $request->request->all();

        if (empty($data['sourceMarkerId']) || empty($data['targetMarkerId'])) {
            return $this->createMessageResponse('error', '参数错误!');
        }

        $this->getMarkerService()->merge($data['sourceMarkerId'], $data['targetMarkerId']);

        return $this->createJsonResponse(true);
    }

    public function markerMetasAction(Request $request, $mediaId)
    {
        $markersMeta = $this->getMarkerService()->findMarkersMetaByMediaId($mediaId);
        $file        = $this->getUploadFileService()->getFile($mediaId);

        $result = array(
            'markersMeta' => $markersMeta,
            'videoTime'   => $file['length']
        );

        return $this->createJsonResponse($result);
    }

    //新增弹题
    public function addQuestionMarkerAction(Request $request, $courseId, $lessonId)
    {
        $data = $request->request->all();

        $lesson = $this->getCourseService()->getLesson($lessonId);

        if (empty($lesson)) {
            return $this->createMessageResponse('error', '该课时不存在!');
        }

        $data['questionId'] = isset($data['questionId']) ? $data['questionId'] : 0;
        $question           = $this->getQuestionService()->getQuestion($data['questionId']);

        if (empty($question)) {
            return $this->createMessageResponse('error', '该题目不存在!');
        }

        if (empty($data['markerId'])) {
            $result = $this->getMarkerService()->addMarker($lesson['mediaId'], $data);
            return $this->createJsonResponse($result);
        } else {
            $marker = $this->getMarkerService()->getMarker($data['markerId']);

            if (!empty($marker)) {
                $questionmarker = $this->getQuestionMarkerService()->addQuestionMarker($data['questionId'], $marker['id'], $data['seq']);
                return $this->createJsonResponse($questionmarker);
            } else {
                return $this->createJsonResponse(false);
            }
        }
    }

    //删除弹题
    public function deleteQuestionMarkerAction(Request $request)
    {
        $data               = $request->request->all();
        $data['questionId'] = isset($data['questionId']) ? $data['questionId'] : 0;
        $result             = $this->getQuestionMarkerService()->deleteQuestionMarker($data['questionId']);
        return $this->createJsonResponse($result);
    }

    //弹题排序
    public function sortQuestionMarkerAction(Request $request)
    {
        $data   = $request->request->all();
        $data   = isset($data['questionIds']) ? $data['questionIds'] : array();
        $result = $this->getQuestionMarkerService()->sortQuestionMarkers($data);
        return $this->createJsonResponse($result);
    }

    //更新驻点时间
    public function updateMarkerAction(Request $request)
    {
        $data       = $request->request->all();
        $data['id'] = isset($data['id']) ? $data['id'] : 0;
        $fields     = array(
            'updatedTime' => time(),
            'second'      => isset($data['second']) ? $data['second'] : ""
        );
        $marker = $this->getMarkerService()->updateMarker($data['id'], $fields);
        return $this->createJsonResponse($marker);
    }

    //获取当前驻点弹题
    public function showQuestionMarkerAction(Request $request)
    {
        $data             = $request->request->all();
        $data['markerId'] = isset($data['markerId']) ? $data['markerId'] : 0;
        $questionMarkers  = $this->getQuestionMarkerService()->findQuestionMarkersByMarkerId($data['markerId']);
        return $this->createJsonResponse($questionMarkers);
    }

    //获取当前播放器的驻点
    public function showMarkersAction(Request $request, $lessonId)
    {
        $data   = $request->request->all();
        $lesson = $this->getCourseService()->getLesson($lessonId);
        //$data['markerId'] = isset($data['markerId']) ? $data['markerId'] : 0;
        $storage      = $this->getSettingService()->get('storage');
        $video_header = $this->getUploadFileService()->getFileByTargetType('headLeader');
        $markers      = $this->getMarkerService()->findMarkersByMediaId($lesson['mediaId']);
        $results      = array();
        $user         = $this->getUserService()->getCurrentUser();

        foreach ($markers as $key => $marker) {
            $results[$key]           = $marker;
            $results[$key]['finish'] = $this->getMarkerService()->isFinishMarker($user['id'], $marker['id']);

            if ($storage['video_header']) {
                $results['videoHeaderTime'] = $video_header['length'];
            }
        }

        return $this->createJsonResponse($results);
    }

    //获取驻点弹题
    public function showMarkerQuestionAction(Request $request, $markerId)
    {
        $user      = $this->getUserService()->getCurrentUser();
        $question  = array();
        $data      = $request->query->all();
        $questions = $this->getQuestionMarkerService()->findQuestionMarkersByMarkerId($markerId);

        if ($this->getMarkerService()->isFinishMarker($user['id'], $markerId)) {
            if (isset($data['questionId'])) {
                $question   = $this->getQuestionMarkerService()->getQuestionMarker($data['questionId']);
                $conditions = array(
                    'seq'      => ++$question['seq'],
                    'markerId' => $markerId
                );
                $question = $this->getQuestionMarkerService()->searchQuestionMarkers($conditions, array('seq', 'ASC'), 0, 1);

                if (!empty($question)) {
                    $question = $question['0'];
                }
            } else {
                $conditions = array(
                    'seq'      => 1,
                    'markerId' => $markerId
                );
                $question = $this->getQuestionMarkerService()->searchQuestionMarkers($conditions, array('seq', 'ASC'), 0, 1);
                $question = $question['0'];
            }
        } else {
            foreach ($questions as $key => $value) {
                $questionResult = $this->getQuestionMarkerResultService()->findByUserIdAndQuestionMarkerId($user['id'], $value['id']);

                if (empty($questionResult)) {
                    $question = $value;
                    break;
                }
            }
        }

        return $this->render('TopxiaWebBundle:Marker:question-modal.html.twig', array(
            'markerId' => $markerId,
            'question' => $question
        ));
    }

    public function doNextTestAction(Request $request)
    {
        $data                 = $request->query->all();
        $data['markerId']     = isset($data['markerId']) ? $data['markerId'] : 0;
        $data['questionId']   = isset($data['questionId']) ? $data['questionId'] : 0;
        $data['answer']       = isset($data['answer']) ? $data['answer'] : null;
        $data['type']         = isset($data['type']) ? $data['type'] : null;
        $user                 = $this->getUserService()->getCurrentUser();
        $questionMarkerResult = $this->getQuestionMarkerResultService()->finishCurrentQuestion($data['markerId'], $user['id'], $data['questionId'], $data['answer'], $data['type']);
        // $conditions = array(
        //     'markerId' => $data['markerId']
        // );
        // $questions = $this->getQuestionMarkerService()->searchQuestionMarkers($conditions, array('seq', 'ASC'), 0, 999);

        // $question = array();

        // foreach ($questions as $key => $value) {
        //     $questionMarkerResult = $this->getQuestionMarkerResultService()->findByUserIdAndQuestionMarkerId($user['id'], $value['id']);

        //     if ($questionMarkerResult['status'] == 'none') {
        //         $question = $value;
        //         break;
        //     }
        // }
        $data = array(
            'markerId'               => $data['markerId'],
            'questionMarkerResultId' => $questionMarkerResult['id']
        );
        return $this->createJsonResponse($data);
    }

    public function showQuestionAnswerAction(Request $request, $questionId)
    {
        $data                 = $request->query->all();
        $user                 = $this->getUserService()->getCurrentUser();
        $questionMarker       = $this->getQuestionMarkerService()->getQuestionMarker($questionId);
        $questionMarkerResult = $this->getQuestionMarkerResultService()->getQuestionMarkerResult($data['questionMarkerResultId']);
        $conditions           = array(
            'markerId' => $data['markerId']
        );
        $count                 = $this->getQuestionMarkerService()->searchQuestionMarkersCount($conditions);
        $questionMarker['seq'] = isset($questionMarker['seq']) ? $questionMarker['seq'] : 1;
        $progress              = array(
            'seq'     => $questionMarker['seq'],
            'count'   => $count,
            'percent' => floor($questionMarker['seq'] / $count * 100)
        );
        return $this->render('TopxiaWebBundle:Marker:answer.html.twig', array(
            'markerId'   => $data['markerId'],
            'question'   => $questionMarker,
            'answer'     => $questionMarker['answer'],
            'selfAnswer' => unserialize($questionMarkerResult['answer']),
            'status'     => $questionMarkerResult['status'],
            'progress'   => $progress
        ));
    }

    public function questionAction(Request $request, $courseId, $lessonId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);
        $lesson = $this->getCourseService()->getCourseLesson($courseId, $lessonId);

        return $this->render('TopxiaWebBundle:Marker:question.html.twig', array(
            'course'        => $course,
            'lesson'        => $lesson,
            'targetChoices' => $this->getQuestionTargetChoices($course, $lesson)
        ));
    }

    public function searchAction(Request $request, $courseId, $lessonId)
    {
        $course = $this->getCourseService()->tryManageCourse($courseId);
        $lesson = $this->getCourseService()->getCourseLesson($courseId, $lessonId);

        $conditions = $request->request->all();

        list($paginator, $questions) = $this->getPaginatorAndQuestion($request, $conditions, $course, $lesson);

        return $this->render('TopxiaWebBundle:Marker:question-tr.html.twig', array(
            'course'        => $course,
            'lesson'        => $lesson,
            'paginator'     => $paginator,
            'questions'     => $questions,
            'targetChoices' => $this->getQuestionTargetChoices($course)
        ));
    }

    protected function getQuestionTargetChoices($course)
    {
        $lessons                           = $this->getCourseService()->getCourseLessons($course['id']);
        $choices                           = array();
        $choices["course-{$course['id']}"] = '本课程';

        foreach ($lessons as $lesson) {
            $choices["course-{$course['id']}/lesson-{$lesson['id']}"] = "课时{$lesson['number']}：{$lesson['title']}";
        }

        return $choices;
    }

    protected function getPaginatorAndQuestion($request, $conditions, $course, $lesson)
    {
        if (!isset($conditions['target']) || empty($conditions['target'])) {
            unset($conditions['target']);
            $conditions['targetPrefix'] = "course-{$course['id']}";
        }

        if (!empty($conditions['keyword'])) {
            $conditions['stem'] = $conditions['keyword'];
        }

        $conditions['parentId'] = 0;
        $conditions['types']    = array('determine', 'single_choice', 'uncertain_choice', 'fill');
        $orderBy                = array('createdTime', 'DESC');
        $paginator              = new Paginator(
            $request,
            $this->getQuestionService()->searchQuestionsCount($conditions),
            5
        );

        $paginator->setPageRange(4);

        $questions = $this->getQuestionService()->searchQuestions(
            $conditions,
            $orderBy,
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );
        // var_dump($paginator->getPerPageCount());
        // exit();
        $markerIds         = ArrayToolkit::column($this->getMarkerService()->findMarkersByMediaId($lesson['mediaId']), 'id');
        $questionMarkerIds = ArrayToolkit::column($this->getQuestionMarkerService()->findQuestionMarkersByMarkerIds($markerIds), 'questionId');

        foreach ($questions as $key => $question) {
            $questions[$key]['exist'] = in_array($question['id'], $questionMarkerIds) ? true : false;
        }

        return array($paginator, $questions);
    }

    protected function getQuestionService()
    {
        return $this->getServiceKernel()->createService('Question.QuestionService');
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }

    protected function getMarkerService()
    {
        return $this->getServiceKernel()->createService('Marker.MarkerService');
    }

    protected function getQuestionMarkerService()
    {
        return $this->getServiceKernel()->createService('Marker.QuestionMarkerService');
    }

    protected function getQuestionMarkerResultService()
    {
        return $this->getServiceKernel()->createService('Marker.QuestionMarkerResultService');
    }

    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
    }

    protected function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    }
}
