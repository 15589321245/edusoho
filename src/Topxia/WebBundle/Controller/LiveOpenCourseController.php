<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Service\CloudPlatform\CloudAPIFactory;
use Topxia\Service\Util\EdusohoLiveClient;

class LiveOpenCourseController extends BaseController
{
    public function entryAction(Request $request, $courseId, $lessonId)
    {
        $lesson = $this->getOpenCourseService()->getLesson($lessonId);

        $result = $this->getLiveCourseService()->checkLessonStatus($lesson);

        if (!$result['result']) {
            return $this->createMessageResponse('info', $result['message']);
        }

        $params = array();

        $params['role'] = $this->getLiveCourseService()->checkCourseUserRole($lesson);

        $liveAccount = CloudAPIFactory::create('leaf')->get('/me/liveaccount');

        $user               = $this->getCurrentUser();
        $params['id']       = $user->isLogin() ? $user['id'] : 0;
        $params['nickname'] = $user->isLogin() ? $user['nickname'] : '游客';

        return $this->forward('TopxiaWebBundle:Liveroom:_entry', array('id' => $lesson['mediaId']), $params);
    }

    public function verifyAction(Request $request)
    {
        $result = array(
            "code" => "0",
            "msg"  => "ok"
        );

        return $this->createJsonResponse($result);
    }

    protected function makeSign($string)
    {
        $secret = $this->container->getParameter('secret');
        return md5($string.$secret);
    }

    public function createLessonReplayAction(Request $request, $courseId, $lessonId)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($courseId);
        $lesson = $this->getOpenCourseService()->getLesson($lessonId);

        if (!$lesson) {
            return $this->createMessageResponse('error', '改课程不存在或已删除！');
        }

        $resultList = $this->getLiveCourseService()->generateLessonReplay($course, $lesson);

        if (isset($resultList['error']) && !empty($resultList['error'])) {
            return $this->createJsonResponse($resultList);
        }

        $client              = new EdusohoLiveClient();
        $lesson              = $this->getOpenCourseService()->getLesson($lessonId);
        $lesson["isEnd"]     = intval(time() - $lesson["endTime"]) > 0;
        $lesson['canRecord'] = $client->isAvailableRecord($lesson['mediaId']);

        return $this->render('TopxiaWebBundle:LiveCourseReplayManage:list-item.html.twig', array(
            'course' => $course,
            'lesson' => $lesson
        ));
    }

    public function editLessonReplayAction(Request $request, $lessonId, $courseId)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($courseId);
        $lesson = $this->getOpenCourseService()->getCourseLesson($courseId, $lessonId);

        if (!$lesson) {
            return $this->createMessageResponse('error', '改课程不存在或已删除！');
        }

        if ($request->getMethod() == 'POST') {
            $ids = $request->request->get("visibleReplaies");
            $this->getCourseService()->updateCourseLessonReplayByLessonId($lessonId, array('hidden' => 1), 'liveOpen');

            foreach ($ids as $id) {
                $this->getCourseService()->updateCourseLessonReplay($id, array('hidden' => 0));
            }

            return $this->redirect($this->generateUrl('live_open_course_manage_replay', array('id' => $courseId)));
        }

        $replayLessons = $this->getCourseService()->searchCourseLessonReplays(array('lessonId' => $lessonId, 'type' => 'liveOpen'), array('replayId', 'ASC'), 0, PHP_INT_MAX);

        return $this->render('TopxiaWebBundle:LiveCourseReplayManage:replay-lesson-modal.html.twig', array(
            'replayLessons' => $replayLessons,
            'lessonId'      => $lessonId,
            'courseId'      => $courseId,
            'lesson'        => $lesson
        ));
    }

    public function updateReplayTitleAction(Request $request, $courseId, $lessonId, $replayId)
    {
        $course = $this->getOpenCourseService()->tryManageOpenCourse($courseId);

        $title = $request->request->get('title');

        if (empty($title)) {
            return $this->createJsonResponse(false);
        }

        $this->getCourseService()->updateCourseLessonReplay($replayId, array('title' => $title));
        return $this->createJsonResponse(true);
    }

    public function replayManageAction(Request $request, $id)
    {
        $course  = $this->getOpenCourseService()->tryManageOpenCourse($id);
        $lessons = $this->getOpenCourseService()->findLessonsByCourseId($course['id']);

        $client = new EdusohoLiveClient();
        foreach ($lessons as $key => $lesson) {
            $lesson["isEnd"]                   = intval(time() - $lesson["endTime"]) > 0;
            $lesson["canRecord"]               = $client->isAvailableRecord($lesson['mediaId']);
            $lessons["lesson-{$lesson['id']}"] = $lesson;
        }

        $default = $this->getSettingService()->get('default', array());
        return $this->render('TopxiaWebBundle:LiveCourseReplayManage:index.html.twig', array(
            'course'  => $course,
            'items'   => $lessons,
            'default' => $default
        ));
    }

    public function entryReplayAction(Request $request, $courseId, $lessonId, $replayId)
    {
        $course = $this->getOpenCourseService()->getCourse($courseId);
        $lesson = $this->getOpenCourseService()->getCourseLesson($courseId, $lessonId);

        return $this->render("TopxiaWebBundle:LiveCourse:classroom.html.twig", array(
            'lesson' => $lesson,
            'url'    => $this->generateUrl('live_open_course_live_replay_url', array(
                'courseId' => $courseId,
                'lessonId' => $lessonId,
                'replayId' => $replayId
            ))
        ));
    }

    public function getReplayUrlAction(Request $request, $courseId, $lessonId, $replayId)
    {
        $course = $this->getOpenCourseService()->getCourse($courseId);
        $result = $this->getLiveCourseService()->entryReplay($replayId);

        return $this->createJsonResponse(array(
            'url'   => $result['url'],
            'param' => isset($result['param']) ? $result['param'] : null
        ));
    }

    protected function getRootCategory($categoryTree, $category)
    {
        $start = false;

        foreach (array_reverse($categoryTree) as $treeCategory) {
            if ($treeCategory['id'] == $category['id']) {
                $start = true;
            }

            if ($start && $treeCategory['depth'] == 1) {
                return $treeCategory;
            }
        }

        return null;
    }

    protected function getOpenCourseService()
    {
        return $this->getServiceKernel()->createService('OpenCourse.OpenCourseService');
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }

    protected function getLiveCourseService()
    {
        return $this->getServiceKernel()->createService('Course.LiveCourseService');
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    }
}
