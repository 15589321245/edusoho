<?php

namespace AppBundle\Component\Export;

class InviteRecordsExporter extends Exporter
{
    public function getTitles()
    {
        return array('邀请人', '注册用户', '订单消费总额', '订单虚拟币总额', '订单现金总额', '邀请码', '邀请时间');
    }

    public function canExport()
    {
        $user = $this->getUser();

        if ($user->hasPermission('admin_operation_invite_record')) {
            return true;
        }

        return false;
    }

    public function getCount()
    {
        $inviteUserCount = $this->getInviteRecordService()->countRecords($this->conditions);
        $invitedUserCount = 0;

        if (!empty($this->conditions['inviteUserId'])) {
            $conditions = $this->conditions;
            $conditions['invitedUserId'] = $conditions['inviteUserId'];
            unset($conditions['inviteUserId']);
            $invitedUserCount = $this->getInviteRecordService()->countRecords($conditions);
        }

        return $inviteUserCount || $invitedUserCount;
    }

    public function getContent($start, $limit)
    {
        $conditions = $this->conditions;

        $recordData = array();
        $records = $this->getInviteRecordService()->searchRecords(
            $conditions,
            array('inviteTime' => 'desc'),
            $start,
            $limit
        );

        if ($start == 0) {
            if (!empty($this->conditions['inviteUserId'])) {
                $invitedRecord = $this->getInviteRecordService()->getRecordByInvitedUserId($this->conditions['inviteUserId']);
                if (!empty($invitedRecord)) {
                    array_unshift($records, $invitedRecord);
                }
            }
        }

        $users = $this->getInviteRecordService()->getAllUsersByRecords($records);

        foreach ($records as $record) {
            $content = $this->exportDataByRecord($record, $users);
            $recordData[] = $content;
        }

        return $recordData;
    }

    protected function exportDataByRecord($record, $users)
    {
        list($coinAmountTotalPrice, $amountTotalPrice, $totalPrice) = $this->getInviteRecordService()->getUserOrderDataByUserIdAndTime($record['invitedUserId'], $record['inviteTime']);
        $content = array();
        $content[] = $users[$record['inviteUserId']]['nickname'];
        $content[] = $users[$record['invitedUserId']]['nickname'];
        $content[] = $totalPrice;
        $content[] = $coinAmountTotalPrice;
        $content[] = $amountTotalPrice;
        $content[] = $users[$record['inviteUserId']]['inviteCode'];
        $content[] = date('Y-m-d H:i:s', $record['inviteTime']);

        return $content;
    }

    public function buildCondition($conditions)
    {
        if (!empty($conditions['nickname'])) {
            $user = $this->getUserService()->getUserByNickname($conditions['nickname']);
            $conditions['inviteUserId'] = empty($user) ? '0' : $user['id'];
            unset($conditions['nickname']);
        }

        return $conditions;
    }

    protected function getUserService()
    {
        return $this->biz->service('User:UserService');
    }

    protected function getInviteRecordService()
    {
        return $this->biz->service('User:InviteRecordService');
    }

    protected function getSettingService()
    {
        return $this->biz->service('System:SettingService');
    }
}
