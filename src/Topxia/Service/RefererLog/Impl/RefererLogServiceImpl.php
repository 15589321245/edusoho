<?php
namespace Topxia\Service\RefererLog\Impl;

use Topxia\Common\ArrayToolkit;
use Topxia\Service\Common\BaseService;
use Topxia\Service\RefererLog\RefererLogService;

class RefererLogServiceImpl extends BaseService implements RefererLogService
{
    public function addRefererLog($refererlog)
    {
        if (!ArrayToolkit::requireds($refererlog, array('targertId', 'targertType', 'refererUrl'))) {
            throw $this->createServiceException("缺少字段添加RefererLog,增加失败");
        }
        if (in_array($refererlog['targertId'], array('course', 'openCourse', 'classroom', 'vip'))) {
            throw $this->createServiceException("模块 {$targertType} 不允许添加RefererLog");
        }

        $user                        = $this->getCurrentUser();
        $refererlog['refererHost']   = $this->prepareRefererUrl($refererlog['refererUrl']);
        $refererlog['createdUserId'] = $user['id'];
        return $this->getRefererLogDao()->addRefererLog($refererlog);
    }

    public function getRefererLogById($id)
    {
        return $this->getRefererLogDao()->getRefererLogById($id);
    }

    protected function getRefererLogDao()
    {
        return $this->createDao('RefererLog.RefererLogDao');
    }

    private function prepareRefererUrl($refererUrl)
    {
        $refererMap = array(
            'baidu.com'        => '百度',
            'mp.weixin.qq.com' => '微信',
            'weibo.com'        => '微博'
        );
        $patten = '/^(https|http)?(:\/\/)?([^\/]+)/';
        preg_match($patten, $refererUrl, $matches);
        $refererHost = $matches[0];
        //  TODO $refererName = array
        return $refererHost;
    }
}
