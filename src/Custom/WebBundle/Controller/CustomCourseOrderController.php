<?php
namespace Custom\WebBundle\Controller;

use Topxia\WebBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Topxia\WebBundle\Controller\CourseOrderController;
use Symfony\Component\HttpFoundation\Response;
use Topxia\WebBundle\Util\AvatarAlert;

class CustomCourseOrderController extends CourseOrderController
{
    public function buyAction(Request $request, $id)
    {   
        $course = $this->getCourseService()->getCourse($id);

        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException();
        }

        $remainingStudentNum = $this->getRemainStudentNum($course);

        $previewAs = $request->query->get('previewAs');

        $member = $user ? $this->getCourseService()->getCourseMember($course['id'], $user['id']) : null;
        $member = $this->previewAsMember($previewAs, $member, $course);

        $courseSetting = $this->getSettingService()->get('course', array());

        $userInfo = $this->getUserService()->getUserProfile($user['id']);
        $userInfo['approvalStatus'] = $user['approvalStatus'];

        $course = $this->getCourseService()->getCourse($id);
       
        $userFields=$this->getUserFieldService()->getAllFieldsOrderBySeqAndEnabled();

        for($i=0;$i<count($userFields);$i++){
           if(strstr($userFields[$i]['fieldName'], "textField")) $userFields[$i]['type']="text";
           if(strstr($userFields[$i]['fieldName'], "varcharField")) $userFields[$i]['type']="varchar";
           if(strstr($userFields[$i]['fieldName'], "intField")) $userFields[$i]['type']="int";
           if(strstr($userFields[$i]['fieldName'], "floatField")) $userFields[$i]['type']="float";
           if(strstr($userFields[$i]['fieldName'], "dateField")) $userFields[$i]['type']="date";
        }

        if ($remainingStudentNum == 0 && $course['type'] == 'live') {
            return $this->render('TopxiaWebBundle:CourseOrder:remainless-modal.html.twig', array(
                'course' => $course
            ));
        }

        $oldOrders = $this->getOrderService()->searchOrders(array(
                'targetType' => 'course',
                'targetId' => $course['id'],
                'userId' => $user['id'],
                'status' => 'created',
                'createdTimeGreaterThan' => strtotime('-40 hours'),
            ), array('createdTime', 'DESC'), 0, 1
        );

        $order = current($oldOrders);

        $vip=$this->getVipService()->getMemberByUserId($user->id);
        $level=array();
        $vipPrice=$course['price'];
        $status="false";
        if($vip){

            $level=$this->getLevelService()->getLevel($vip['levelId']);

            if($level && $this->getVipService()->checkUserInMemberLevel($user->id,$vip['levelId'])=="ok"){
               
                $status=$this->getVipService()->checkUserInMemberLevel($user->id,$vip['levelId']);
                $vipPrice=$course['price']*0.1*$level['courseDiscount'];
                $vipPrice=sprintf("%.2f", $vipPrice);

                $order['amount']=$order['amount']/$level['courseDiscount']*10;
                
                $order['amount']=sprintf("%.2f", $order['amount']);

                if(isset($course['coinPrice'])){

                    $course['vipCoinPrice']=$course['coinPrice']*0.1*$level['courseDiscount'];
                    $course['vipCoinPrice']=sprintf("%.2f", $course['vipCoinPrice']);
                }
            }
        }

        if($course['price'] > 0 && $order && isset($order['couponDiscount']) && ($course['price'] == ($order['amount'] + $order['couponDiscount'])) ) {
          
             return $this->render('TopxiaWebBundle:CourseOrder:repay.html.twig', array(
                'order' => $order,
            ));
        }

        $account=$this->getCashAccountService()->getAccountByUserId($user['id'],false);
        
        if(empty($account)){
            $this->getCashService()->createAccount($user['id']);
        }

        if(isset($account['cash']))
        $account['cash']=intval($account['cash']);

        return $this->render('CustomWebBundle:CourseOrder:buy-modal.html.twig', array(
            'course' => $course,
            'payments' => $this->getEnabledPayments(),
            'user' => $userInfo,
            'avatarAlert' => AvatarAlert::alertJoinCourse($user),
            'courseSetting' => $courseSetting,
            'member' => $member,
            'userFields'=>$userFields,
            'level'=>$level,
            'status'=>$status,
            'vipPrice'=>$vipPrice,
            'account'=>$account,
        ));
    }
    
    public function repayAction(Request $request)
    {   
        $user=$this->getCurrentUser();
        $order = $this->getOrderService()->getOrder($request->request->get('orderId'));
        
        $vip=$this->getVipService()->getMemberByUserId($user->id);
        $level=array();

        if($vip){

            $level=$this->getLevelService()->getLevel($vip['levelId']);

            if($level && $this->getVipService()->checkUserInMemberLevel($user->id,$vip['levelId'])=="ok"){
                 
                $order['amount']=$order['amount']/$level['courseDiscount']*10;
                
                $order['amount']=sprintf("%.2f", $order['amount']);
            }
        }
        if (empty($order)) {
            return $this->createMessageResponse('error', '订单不存在!');
        }

        if ( (time() - $order['createdTime']) > 40 * 3600 ) {
            return $this->createMessageResponse('error', '订单已过期，不能支付，请重新创建订单。');
        }

        if ($order['targetType'] != 'course') {
            return $this->createMessageResponse('error', '此类订单不能支付，请重新创建订单!');
        }

        $course = $this->getCourseService()->getCourse($order['targetId']);
        if (empty($course)) {
            return $this->createMessageResponse('error', '购买的课程不存在，请重新创建订单!');
        }

        if ($course['price'] != ($order['amount'] + $order['couponDiscount'])) {
            return $this->createMessageResponse('error', '订单价格已变更，请重新创建订单!');
        }

        if($vip){

            $level=$this->getLevelService()->getLevel($vip['levelId']);

            if($level && $this->getVipService()->checkUserInMemberLevel($user->id,$vip['levelId'])=="ok"){
                 
                $order['amount']=$order['amount']*$level['courseDiscount']*0.1;
                
                $order['amount']=sprintf("%.2f", $order['amount']);
            }
        }

        $payRequestParams = array(
            'returnUrl' => $this->generateUrl('course_order_pay_return', array('name' => $order['payment']), true),
            'notifyUrl' => $this->generateUrl('course_order_pay_notify', array('name' => $order['payment']), true),
            'showUrl' => $this->generateUrl('course_show', array('id' => $order['targetId']), true),
        );

        return $this->forward('TopxiaWebBundle:Order:submitPayRequest', array(
            'order' => $order,
            'requestParams' => $payRequestParams,
        ));
    }

    public function payReturnAction(Request $request, $name)
    {
        $controller = $this;
        return $this->doPayReturn($request, $name, function($success, $order) use(&$controller) {
            if (!$success) {
                $controller->generateUrl('course_show', array('id' => $order['targetId']));
            }

            $controller->getCourseOrderService()->doSuccessPayOrder($order['id']);

            return $controller->generateUrl('course_show', array('id' => $order['targetId']));
        });
    }

    public function payNotifyAction(Request $request, $name)
    {
        $controller = $this;
        return $this->doPayNotify($request, $name, function($success, $order) use(&$controller) {
            if (!$success) {
                return ;
            }

            $controller->getCourseOrderService()->doSuccessPayOrder($order['id']);

            return ;
        });
    }

    protected function getCashService(){
      
        return $this->getServiceKernel()->createService('Cash.CashService');
    }

    protected function getCashAccountService(){
        return $this->getServicekernel()->createService('Cash.CashAccountService');
    }


    private function getNotificationService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }

    private function getEnabledPayments()
    {
        $enableds = array();

        $setting = $this->setting('payment', array());

        if (empty($setting['enabled'])) {
            return $enableds;
        }

        $payNames = array('alipay');
        foreach ($payNames as $payName) {
            if (!empty($setting[$payName . '_enabled'])) {
                $enableds[$payName] = array(
                    'type' => empty($setting[$payName . '_type']) ? '' : $setting[$payName . '_type'],
                );
            }
        }

        return $enableds;
    }

        private function previewAsMember($as, $member, $course)
    {
        $user = $this->getCurrentUser();
        if (empty($user->id)) {
            return null;
        }


        if (in_array($as, array('member', 'guest'))) {
            if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
                $member = array(
                    'id' => 0,
                    'courseId' => $course['id'],
                    'userId' => $user['id'],
                    'levelId' => 0,
                    'learnedNum' => 0,
                    'isLearned' => 0,
                    'seq' => 0,
                    'isVisible' => 0,
                    'role' => 'teacher',
                    'locked' => 0,
                    'createdTime' => time(),
                    'deadline' => 0
                );
            }

            if (empty($member) or $member['role'] != 'teacher') {
                return $member;
            }

            if ($as == 'member') {
                $member['role'] = 'student';
            } else {
                $member = null;
            }
        }

        return $member;
    }

    private function getRemainStudentNum($course)
    {
        $remainingStudentNum = $course['maxStudentNum'];

        if ($course['type'] == 'live') {
            if ($course['price'] <= 0) {
                $remainingStudentNum = $course['maxStudentNum'] - $course['studentNum'];
            } else {
                $createdOrdersCount = $this->getOrderService()->searchOrderCount(array(
                    'targetType' => 'course',
                    'targetId' => $course['id'],
                    'status' => 'created',
                    'createdTimeGreaterThan' => strtotime("-30 minutes")
                ));
                $remainingStudentNum = $course['maxStudentNum'] - $course['studentNum'] - $createdOrdersCount;
            }
        }

        return $remainingStudentNum;
    }

    protected function getVipService()
    {
        return $this->getServiceKernel()->createService('Vip:Vip.VipService');
    } 

    protected function getLevelService()
    {
        return $this->getServiceKernel()->createService('Vip:Vip.LevelService');
    }
    
    public function getCourseOrderService()
    {
        return $this->getServiceKernel()->createService('Custom:Course.CustomCourseOrderService');
    }

    protected function getOrderService()
    {
        return $this->getServiceKernel()->createService('Custom:Order.CustomOrderService');
    }
}