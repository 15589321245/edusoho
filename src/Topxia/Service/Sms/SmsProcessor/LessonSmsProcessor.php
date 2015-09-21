<?php
namespace Topxia\Service\Sms\SmsProcessor;

use Topxia\Service\Common\ServiceKernel;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\NumberToolkit;
use Topxia\Common\CurlToolkit;

class LessonSmsProcessor extends BaseProcessor implements SmsProcessor
{
    public function getUrls($targetId, $smsType)
    {
        $lesson = $this->getCourseService()->getLesson($targetId);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);
        if ($course['parentId'] ) {
            $classroom = $this->getClassroomService()->findClassroomByCourseId($course['id']);
            if ($classroom) {
                $count = $this->getClassroomService()->searchMemberCount(array('classroomId' => $classroom['classroomId']));
            }
        } else {
            $count = $this->getCourseService()->searchMemberCount(array('courseId' => $course['id']));
        }
        global $kernel;
        $container = $kernel->getContainer();
        $serviceKernel = ServiceKernel::create($kernel->getEnvironment(), $kernel->isDebug());
        $hostName = $serviceKernel->getEnvVariable('schemeAndHost');
        for($i = 0; $i <= $count/1000; $i ++){
            $urls[$i] = $hostName;
            $urls[$i] .= $container->get('router')->generate('edu_cloud_sms_send_callback',array('targetType' => 'lesson','targetId' => $targetId));
            $urls[$i] .= '?index='.($i * 1000);
            $token = $this->getTokenService()->makeToken('sms_send', array('data' => array('targetType' => 'lesson', 'targetId' => $targetId, 'index' => $i * 1000)));
            $urls[$i] .= '&token='.$token['token'].'&smsType='.$smsType;
        }
        return array('count' => $count, 'urls' => $urls);
    }

	public function getSmsInfo($targetId, $index, $smsType)
    {
        global $kernel;
        $serviceKernel = ServiceKernel::create($kernel->getEnvironment(), $kernel->isDebug());
        $hostName = $serviceKernel->getEnvVariable('schemeAndHost');
        $lesson = $this->getCourseService()->getLesson($targetId);
        if (empty($lesson)) {
            throw new \RuntimeException('课时不存在');
        }
        $originUrl = $hostName;
        $originUrl .= $kernel->getContainer()->get('router')->generate('course_learn',array('id' => $lesson['courseId']));
        $originUrl .= '#lesson/'.$lesson['id'];
        $url = $this->changeLink($originUrl);
        $course = $this->getCourseService()->getCourse($lesson['courseId']);
        $to = '';

        if ($course['parentId'] ) {
            $classroom = $this->getClassroomService()->findClassroomByCourseId($course['id']);
            if ($classroom) {
                $students = $this->getClassroomService()->searchMembers(array('classroomId' => $classroom['classroomId']), array('createdTime','Desc'), $index, 1000);
            }
        } else {
            $students = $this->getCourseService()->searchMembers(array('courseId' => $course['id']),array('createdTime','Desc'), $index, 1000);
        }
        $studentIds = ArrayToolkit::column($students, 'userId');
        $users = $this->getUserService()->findUsersByIds($studentIds);
        foreach ($users as $key => $value ) {
            if (strlen($value['verifiedMobile']) == 0) {
                unset($users[$key]);
            }
        }
        if ($users) {
            $verifiedMobiles = ArrayToolkit::column($users, 'verifiedMobile');
            $to = implode(',', $verifiedMobiles);
        }

        $parameters['lesson_title'] = '《'.$lesson['title'].'》';
        $parameters['startTime'] = date("Y-m-d h:i:s", $lesson['startTime']);
        $parameters['course_title'] = '《'.$course['title'].'》';
        $description = $parameters['course_title'].' '.$parameters['lesson_title'].'预告';
        $parameters['url'] = $url;

        return array('mobile' => $to, 'category' => $smsType, 'description' => $description, 'parameters' => $parameters);
    }

    private function changeLink($url)
    {
        $arrResponse = json_decode(CurlToolkit::postRequest("http://dwz.cn/create.php",array('url' => $url)),true);
        if ($arrResponse['status'] != 0) {
            throw new \RuntimeException("短链接生成失败!");
        }
        $shortUrl = $arrResponse['tinyurl'];

        return $shortUrl;
    }

    protected function getUserService()
    {
        return ServiceKernel::instance()->createService('User.UserService');
    }

    protected function getCourseService()
    {
        return ServiceKernel::instance()->createService('Course.CourseService');
    }

    protected function getTokenService()
    {
        return ServiceKernel::instance()->createService('User.TokenService');
    }

}