<?php

namespace Biz\Classroom\Dao;

use Codeages\Biz\Framework\Dao\GeneralDaoInterface;

interface ClassroomMemberDao extends GeneralDaoInterface
{
    public function countStudents($classroomId);

    public function countAuditors($classroomId);

    public function getByClassroomIdAndUserId($classroomId, $userId);

    public function deleteByClassroomIdAndUserId($classroomId, $userId);

    public function findTeachersByClassroomId($classroomId);

    public function findAssistantsByClassroomId($classroomId);

    public function findByUserIdAndClassroomIds($userId, array $classroomIds);

    public function findByClassroomIdAndRole($classroomId, $role, $start, $limit);

    public function findByClassroomIdAndUserIds($classroomId, $userIds);

    public function countMobileVerifiedMembersByClassroomId($classroomId, $userLocked);

    public function findMemberIdsByClassroomId($classroomId);

    public function findByUserId($userId);

    public function countMobileFilledMembersByClassroomId($classroomId, $userLocked = 0);

    public function updateMembersDeadlineByClassroomId($classroomId, $deadline);

    public function findMembersByUserId($userId);
}
