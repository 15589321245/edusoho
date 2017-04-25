<?php

namespace ApiBundle\Api\Resource\Course;

use ApiBundle\Api\Resource\Filter;
use ApiBundle\Api\Resource\User\UserFilter;

class CourseMemberFilter extends Filter
{
    protected $publicFields = array(
        'id', 'courseId', 'user', 'deadline', 'levelId', 'learnedNum', 'noteNum',
        'noteLastUpdateTime', 'isLearned', 'finishedTime', 'role', 'locked', 'createdTime',
        'lastLearnTime', 'lastViewTime', 'courseSetId', 'access'
    );

    protected function publicFields(&$data)
    {
        if ($data['deadline']) {
            $data['deadline'] = date('c', $data['deadline']);
        }

        $data['noteLastUpdateTime'] = date('c', $data['noteLastUpdateTime']);
        $data['finishedTime'] = date('c', $data['finishedTime']);
        $data['lastLearnTime'] = date('c', $data['lastLearnTime']);
        $data['lastViewTime'] = date('c', $data['lastViewTime']);

        $userFilter = new UserFilter();
        $userFilter->filter($data['user']);
    }
}