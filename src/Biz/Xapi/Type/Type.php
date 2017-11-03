<?php

namespace Biz\Xapi\Type;

use Biz\Course\Service\CourseNoteService;
use Biz\Course\Service\ThreadService;
use Biz\Task\Service\TaskResultService;
use Biz\Testpaper\Service\TestpaperService;
use Biz\User\Service\UserService;
use Biz\Xapi\Service\XapiService;
use Codeages\Biz\Framework\Context\BizAware;
use QiQiuYun\SDK\Auth;

abstract class Type extends BizAware
{
    abstract public function package($statement);

    protected function createService($alias)
    {
        return $this->biz->service($alias);
    }

    protected function getActivityService()
    {
        return $this->createService('Activity:ActivityService');
    }

    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }

    /**
     * @return CourseNoteService
     */
    protected function getCourseNoteService()
    {
        return $this->createService('Course:CourseNoteService');
    }

    /**
     * @return TaskResultService
     */
    protected function getTaskResultService()
    {
        return $this->createService('Task:TaskResultService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    protected function getCourseService()
    {
        return $this->createService('Course:CourseService');
    }

    protected function getMemberService()
    {
        return $this->createService('Course:MemberService');
    }

    /**
     * @return XapiService
     */
    protected function getXapiService()
    {
        return $this->createService('Xapi:XapiService');
    }

    protected function getTaskService()
    {
        return $this->createService('Task:TaskService');
    }

    protected function getCourseSetService()
    {
        return $this->createService('Course:CourseSetService');
    }

    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    protected function getQuestionMarkerResultService()
    {
        return $this->createService('Marker:QuestionMarkerResultService');
    }

    protected function getMarkerService()
    {
        return $this->createService('Marker:MarkerService');
    }

    protected function getQuestionMarkerService()
    {
        return $this->createService('Marker:QuestionMarkerService');
    }

    /**
     * @return TestpaperService
     */
    protected function getTestpaperService()
    {
        return $this->createService('Testpaper:TestpaperService');
    }

    /**
     * @return ThreadService
     */
    protected function getThreadService()
    {
        return $this->createService('Course:ThreadService');
    }

    protected function getActor($userId)
    {
        $currentUser = $this->getUserService()->getUser($userId);
        $siteSettings = $this->getSettingService()->get('site', array());

        $host = empty($siteSettings['url']) ? '' : $siteSettings['url'];

        return array(
            'account' => array(
                'id' => $currentUser['id'],
                'name' => $currentUser['nickname'],
                'email' => $currentUser['email'],
                'mobile' => empty($currentUser['mobile']) ? '' : $currentUser['mobile'],
                'homePage' => $host,
            ),
        );
    }

    public function createXAPIService()
    {
        $settings = $this->getSettingService()->get('storage', array());
        $siteSettings = $this->getSettingService()->get('site', array());

        $siteName = empty($siteSettings['name']) ? '' : $siteSettings['name'];
        $siteUrl = empty($siteSettings['url']) ? '' : $siteSettings['url'];
        $accessKey = empty($settings['cloud_access_key']) ? '' : $settings['cloud_access_key'];
        $secretKey = empty($settings['cloud_secret_key']) ? '' : $settings['cloud_secret_key'];
        $auth = new Auth('9DdikSDLhmObBhE0t3mhN9UUl8FW2Zdh', 'jNqSV44Fx5kxBFc4VI840pLk8D6QeO86');

        return new \QiQiuYun\SDK\Service\XAPIService($auth, array(
            'base_uri' => 'http://192.168.4.214:8769/v1/xapi/', //推送的URL需要配置
            'school' => array(
                'accessKey' => $accessKey,
                'url' => $siteUrl,
                'name' => $siteName,
            ),
        ));
    }

    protected function num_to_capital($num)
    {
        $char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return $char[$num];
    }

    protected function convertMediaType($mediaType)
    {
        $list = array(
            'audio' => 'audio',
            'video' => 'video',
            'doc' => 'document',
            'ppt' => 'document',
            'testpaper' => 'testpaper',
            'homework' => 'homework',
            'exercise' => 'exercise',
            'download' => 'download',
            'live' => 'live',
            'text' => 'text',
            'flash' => 'flash',
        );

        return empty($list[$mediaType]) ? $mediaType : $list[$mediaType];
    }
}
