<?php

namespace AppBundle\Controller\Activity;

use Biz\Course\Service\CourseService;
use AppBundle\Controller\BaseController;
use Biz\Activity\Service\ActivityLearnLogService;
use Biz\Activity\Service\ActivityService;
use Symfony\Component\HttpFoundation\Request;

class VideoController extends BaseController implements ActivityActionInterface
{
    public function showAction(Request $request, $activity)
    {
        $video       = $this->getActivityService()->getActivityConfig($activity['mediaType'])->get($activity['mediaId']);
        $watchStatus = $this->getWatchStatus($activity);

        if ($watchStatus['status'] == 'error') {
            return $this->render('activity/video/limit.html.twig', array(
                'watchStatus' => $watchStatus
            ));
        }
        return $this->render('activity/video/show.html.twig', array(
            'activity' => $activity,
            'video'    => $video,
        ));
    }

    public function previewAction(Request $request, $task)
    {
        $activity = $this->getActivityService()->getActivity($task['activityId'], $fetchMedia = true);

        $course  = $this->getCourseService()->getCourse($task['courseId']);
        $user    = $this->getCurrentUser();
        $context = array();

        if ($task['mediaSource'] != 'self') {
            if ($task['mediaSource'] == 'youku') {
                $matched = preg_match('/\/sid\/(.*?)\/v\.swf/s', $activity['ext']['mediaUri'], $matches);

                if ($matched) {
                    $task['mediaUri']    = "http://player.youku.com/embed/{$matches[1]}";
                    $task['mediaSource'] = 'iframe';
                }
            } elseif ($task['mediaSource'] == 'tudou') {
                $matched = preg_match('/\/v\/(.*?)\/v\.swf/s', $activity['ext']['mediaUri'], $matches);

                if ($matched) {
                    $task['mediaUri']    = "http://www.tudou.com/programs/view/html5embed.action?code={$matches[1]}";
                    $task['mediaSource'] = 'iframe';
                }
            }
        } else {
            $context['hideQuestion'] = 1;
            $context['hideSubtitle'] = 0;

            if (!$task["isFree"] && !empty($course['tryLookable'])) {
                $context['starttime']      = $request->query->get('starttime');
                $context['hideBeginning']  = $request->query->get('hideBeginning', false);
                $context['watchTimeLimit'] = $course['tryLookLength'] * 60;
            }
        }

        return $this->render('activity/video/preview.html.twig', array(
            'activity' => $activity,
            'course'   => $course,
            'task'     => $task,
            'user'     => $user,
            'context'  => $context
        ));
    }

    /**
     * 获取当前视频活动的文件来源
     * @param  $activity
     * @return mediaSource
     */
    protected function getMediaSource($activity)
    {
        return $activity['ext']['mediaSource'];
    }

    public function editAction(Request $request, $id, $courseId)
    {
        $activity = $this->getActivityService()->getActivity($id, $fetchMedia = true);
        $activity = $this->fillMinuteAndSecond($activity);
        return $this->render('activity/video/modal.html.twig', array(
            'activity' => $activity,
            'courseId' => $courseId
        ));
    }

    public function createAction(Request $request, $courseId)
    {
        return $this->render('activity/video/modal.html.twig', array(
            'courseId' => $courseId
        ));
    }

    protected function fillMinuteAndSecond($activity)
    {
        if (!empty($activity['length'])) {
            $activity['minute'] = intval($activity['length'] / 60);
            $activity['second'] = intval($activity['length'] % 60);
        }
        return $activity;
    }

    public function finishConditionAction($activity)
    {
        return $this->render('activity/video/finish-condition.html.twig', array());
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

    /**
     * @return ActivityLearnLogService
     */
    protected function getActivityLearnLogService()
    {
        return $this->createService('Activity:ActivityLearnLogService');
    }

    /**
     * get the information if the video can be watch
     * @param $task
     * @return null
     */
    protected function getWatchStatus($activity)
    {
        $user      = $this->getCurrentUser();
        $watchTime = $this->getActivityLearnLogService()->sumWatchTimeByActivityIdAndUserId($activity['id'], $user['id']);

        $course      = $this->getCourseService()->getCourse($activity['fromCourseId']);
        $watchStatus = array();
        if ($this->setting('magic.lesson_watch_limit') && $course['watchLimit'] > 0) {

            //只有视频课程才限制观看时长
            if (empty($course['watchLimit']) || $activity['mediaType'] != 'video') {
                return array('status' => 'ignore');
            }

            $watchLimitTime = $activity['length'] * $course['watchLimit'];
            if (empty($watchTime)) {
                return array('status' => 'ok', 'watchedTime' => 0, 'watchLimitTime' => $watchLimitTime);
            }
            if ($watchTime < $watchLimitTime) {
                return array('status' => 'ok', 'watchedTime' => $watchTime, 'watchLimitTime' => $watchLimitTime);
            }

            return array('status' => 'error', 'watchedTime' => $watchTime, 'watchLimitTime' => $watchLimitTime);

        }
        return $watchStatus;
    }

    public function watchAction(Request $request, $courseId, $id)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException();
        }

        $activity = $this->getActivityService()->getActivity($id);

        $isLimit = $this->setting('magic.lesson_watch_limit');
        if ($isLimit) {
            $watchStatus = $this->getWatchStatus($activity);

            return $this->createJsonResponse($watchStatus);
        }
        return $this->createJsonResponse(array('status' => 'ok'));
    }
}

