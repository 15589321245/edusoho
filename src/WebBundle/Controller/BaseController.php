<?php

namespace WebBundle\Controller;

use Codeages\Biz\Framework\Service\BaseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Topxia\Common\Exception\ResourceNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseController extends Controller
{
    protected function getBiz()
    {
        return $this->get('biz');
    }

    public function getUser()
    {
        $biz = $this->getBiz();
        return $biz['user'];
    }

    protected function createJsonResponse($data = null, $status = 200, $headers = array())
    {
        return new JsonResponse($data, $status, $headers);
    }

    protected function createResourceNotFoundException($resourceType, $resourceId, $message = '')
    {
        return new ResourceNotFoundException($resourceType, $resourceId, $message);
    }

    /**
     * 创建消息提示响应
     *
     * @param  string     $type     消息类型：info, warning, error
     * @param  string     $message  消息内容
     * @param  string     $title    消息抬头
     * @param  integer    $duration 消息显示持续的时间
     * @param  string     $goto     消息跳转的页面
     * @return Response
     */
    protected function createMessageResponse($type, $message, $title = '', $duration = 0, $goto = null)
    {
        if (!in_array($type, array('info', 'warning', 'error'))) {
            throw new \RuntimeException('type error');
        }

        return $this->render('TopxiaWebBundle:Default:message.html.twig', array(
            'type'     => $type,
            'message'  => $message,
            'title'    => $title,
            'duration' => $duration,
            'goto'     => $goto
        ));
    }

    /**
     * @param  string        $alias
     * @return BaseService
     */
    protected function createService($alias)
    {
        $biz = $this->getBiz();
        return $biz->service($alias);
    }

    protected function setFlashMessage($level, $message)
    {
        $this->get('session')->getFlashBag()->add($level, $message);
    }
}
