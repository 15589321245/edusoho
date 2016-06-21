<?php

namespace Topxia\Service\RefererLog\Dao;

interface OrderRefererLogDao
{
    public function getOrderRefererLog($id);

    public function addOrderRefererLog($fields);

    public function updateOrderRefererLog($id, $fields);

    public function deleteOrderRefererLog($id);

    public function searchOrderRefererLogs($conditions, $orderBy, $start, $limit);

    public function searchOrderRefererLogCount($conditions);

}
