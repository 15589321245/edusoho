<?php

namespace Topxia\Service\OpenCourse\Impl;

use Topxia\Common\ArrayToolkit;
use Topxia\Service\Common\BaseService;
use Topxia\Service\Common\ServiceEvent;
use Topxia\Service\OpenCourse\OpenCourseService;

class OpenCourseServiceImpl extends BaseService implements OpenCourseService
{
    /**
     * open_course
     */
    public function getCourse($id)
    {
        return $this->getOpenCourseDao()->getCourse($id);
    }

    public function findCoursesByIds(array $ids)
    {
        return $this->getOpenCourseDao()->findCoursesByIds($ids);
    }

    public function findCoursesByParentIdAndLocked($parentId, $locked)
    {
        return $this->getOpenCourseDao()->findCoursesByParentIdAndLocked($parentId, $locked);
    }

    public function searchCourses($conditions, $orderBy, $start, $limit)
    {
        return $this->getOpenCourseDao()->searchCourses($conditions, $orderBy, $start, $limit);
    }

    public function searchCourseCount($conditions)
    {
        return $this->getOpenCourseDao()->searchCourseCount($conditions);
    }

    public function createCourse($course)
    {
        if (!ArrayToolkit::requireds($course, array('title'))) {
            throw $this->createServiceException('缺少必要字段，创建课程失败！');
        }

        $course                = ArrayToolkit::parts($course, array('title', 'type', 'about', 'categoryId', 'tags'));
        $course['status']      = 'draft';
        $course['about']       = !empty($course['about']) ? $this->purifyHtml($course['about']) : '';
        $course['tags']        = !empty($course['tags']) ? array($course['tags']) : array();
        $course['userId']      = $this->getCurrentUser()->id;
        $course['createdTime'] = time();
        $course['teacherIds']  = array($course['userId']);

        $course = $this->getOpenCourseDao()->addCourse($course);

        $member = array(
            'courseId'    => $course['id'],
            'userId'      => $course['userId'],
            'role'        => 'teacher',
            'createdTime' => time()
        );

        $this->getOpenCourseMemberDao()->addMember($member);

        $this->getLogService()->info('openCourse', 'create', "创建公开课《{$course['title']}》(#{$course['id']})");

        return $course;
    }

    public function updateCourse($id, $fields)
    {
        $argument = $fields;
        $course   = $this->getOpenCourseDao()->getCourse($id);

        if (empty($course)) {
            throw $this->createServiceException('课程不存在，更新失败！');
        }

        $fields = $this->_filterCourseFields($fields);

        $this->getLogService()->info('openCourse', 'update', "更新公开课课程《{$course['title']}》(#{$course['id']})的信息", $fields);

        $updatedCourse = $this->getOpenCourseDao()->updateCourse($id, $fields);

        $this->dispatchEvent("open.course.update", array('argument' => $argument, 'course' => $updatedCourse));

        return $this->getOpenCourseDao()->updateCourse($id, $fields);
    }

    public function deleteCourse($id)
    {
        return $this->getOpenCourseDao()->deleteCourse($id);
    }

    public function waveCourse($id, $field, $diff)
    {
        return $this->getOpenCourseDao()->waveCourse($id, $field, $diff);
    }

    public function publishCourse($id)
    {
        $lessonCount = $this->searchLessonCount(array('courseId' => $id, 'status' => 'published'));

        if ($lessonCount < 1) {
            return array('result' => false, 'message' => '请先添加课时并发布！');
        }

        $course = $this->updateCourse($id, array('status' => 'published'));

        return array('result' => true, 'course' => $course);
    }

    public function closeCourse($id)
    {
        $course = $this->tryManageOpenCourse($id);

        if (empty($course)) {
            throw $this->createNotFoundException();
        }

        $this->getOpenCourseDao()->updateCourse($id, array('status' => 'closed'));
    }

    public function tryManageOpenCourse($courseId)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('未登录用户，无权操作！');
        }

        $course = $this->getOpenCourseDao()->getCourse($courseId);

        if (empty($course)) {
            throw $this->createNotFoundException();
        }

        if (!$this->hasOpenCourseManagerRole($courseId, $user['id'])) {
            throw $this->createAccessDeniedException('您不是课程的教师或管理员，无权操作！');
        }

        return $course;
    }

    public function changeCoursePicture($courseId, $data)
    {
        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createServiceException('课程不存在，图标更新失败！');
        }

        $fileIds = ArrayToolkit::column($data, "id");
        $files   = $this->getFileService()->getFilesByIds($fileIds);

        $files   = ArrayToolkit::index($files, "id");
        $fileIds = ArrayToolkit::index($data, "type");

        $fields = array(
            'smallPicture'  => $files[$fileIds["small"]["id"]]["uri"],
            'middlePicture' => $files[$fileIds["middle"]["id"]]["uri"],
            'largePicture'  => $files[$fileIds["large"]["id"]]["uri"]
        );

        $this->_deleteNotUsedPictures($course);

        $this->getLogService()->info('open_course', 'update_picture', "更新公开课《{$course['title']}》(#{$course['id']})图片", $fields);

        $update_picture = $this->getOpenCourseDao()->updateCourse($courseId, $fields);

        $this->dispatchEvent("open.course.picture.update", array('argument' => $data, 'course' => $update_picture));

        return $update_picture;
    }

    public function favoriteCourse($courseId)
    {
        $user = $this->getCurrentUser();

        if (empty($user['id'])) {
            throw $this->createAccessDeniedException();
        }

        $course = $this->getCourse($courseId);

/*if ($course['status'] != 'published') {
throw $this->createServiceException('不能收藏未发布课程');
}*/

        if (empty($course)) {
            throw $this->createServiceException("该课程不存在,收藏失败!");
        }

        $favorite = $this->getFavoriteDao()->getFavoriteByUserIdAndCourseId($user['id'], $course['id'], 'openCourse');

        if ($favorite) {
            throw $this->createServiceException("该收藏已经存在，请不要重复收藏!");
        }

        //添加动态
        $this->dispatchEvent(
            'open.course.favorite',
            new ServiceEvent($course)
        );

        $this->getFavoriteDao()->addFavorite(array(
            'courseId'    => $course['id'],
            'userId'      => $user['id'],
            'createdTime' => time(),
            'type'        => 'openCourse'
        ));

        $courseFavoriteNum = $this->getFavoriteDao()->searchCourseFavoriteCount(array(
            'courseId' => $courseId,
            'type'     => 'openCourse'
        ));

        return $courseFavoriteNum;
    }

    public function unFavoriteCourse($courseId)
    {
        $user = $this->getCurrentUser();

        if (empty($user['id'])) {
            throw $this->createAccessDeniedException();
        }

        $course = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createServiceException("该课程不存在,收藏失败!");
        }

        $favorite = $this->getFavoriteDao()->getFavoriteByUserIdAndCourseId($user['id'], $course['id'], 'openCourse');

        if (empty($favorite)) {
            throw $this->createServiceException("你未收藏本课程，取消收藏失败!");
        }

        $this->getFavoriteDao()->deleteFavorite($favorite['id']);

        $courseFavoriteNum = $this->getFavoriteDao()->searchCourseFavoriteCount(array(
            'courseId' => $courseId,
            'type'     => 'openCourse'
        ));

        return $courseFavoriteNum;
    }

    public function getCourseItems($courseId)
    {
        $lessons = $this->getOpenCourseLessonDao()->findLessonsByCourseId($courseId);

        $items = array();

        foreach ($lessons as $lesson) {
            $lesson['itemType']              = 'lesson';
            $items["lesson-{$lesson['id']}"] = $lesson;
        }

        uasort($items, function ($item1, $item2) {
            return $item1['seq'] > $item2['seq'];
        }

        );
        return $items;
    }

    /**
     * open_course_lesson
     */
    public function getLesson($id)
    {
        return $this->getOpenCourseLessonDao()->getLesson($id);
    }

    public function findLessonsByIds(array $ids)
    {
        return $this->getOpenCourseLessonDao()->findLessonsByIds($ids);
    }

    public function findLessonsByCourseId($courseId)
    {
        return $this->getOpenCourseLessonDao()->findLessonsByCourseId($courseId);
    }

    public function searchLessons($condition, $orderBy, $start, $limit)
    {
        return $this->getOpenCourseLessonDao()->searchLessons($condition, $orderBy, $start, $limit);
    }

    public function searchLessonCount($conditions)
    {
        return $this->getOpenCourseLessonDao()->searchLessonCount($conditions);
    }

    public function createLesson($lesson)
    {
        $lesson = ArrayToolkit::filter($lesson, array(
            'courseId'      => 0,
            'chapterId'     => 0,
            'seq'           => 0,
            'free'          => 0,
            'title'         => '',
            'summary'       => '',
            'tags'          => array(),
            'type'          => 'text',
            'content'       => '',
            'media'         => array(),
            'mediaId'       => 0,
            'length'        => 0,
            'startTime'     => 0,
            'giveCredit'    => 0,
            'requireCredit' => 0,
            'liveProvider'  => 'none',
            'copyId'        => 0,
            'testMode'      => 'normal',
            'testStartTime' => 0,
            'suggestHours'  => '0.0',
            'copyId'        => 0,
            'status'        => 'unpublished'
        ));

        if (!ArrayToolkit::requireds($lesson, array('courseId', 'title', 'type'))) {
            throw $this->createServiceException('参数缺失，创建课时失败！');
        }

        if (empty($lesson['courseId'])) {
            throw $this->createServiceException('添加课时失败，课程ID为空。');
        }

        $course = $this->getCourse($lesson['courseId'], true);

        if (empty($course)) {
            throw $this->createServiceException('添加课时失败，课程不存在。');
        }

        if (!in_array($lesson['type'], array('text', 'audio', 'video', 'liveOpen', 'open', 'ppt', 'document', 'flash'))) {
            throw $this->createServiceException('课时类型不正确，添加失败！');
        }

        $this->fillLessonMediaFields($lesson);

        if (isset($fields['title'])) {
            $fields['title'] = $this->purifyHtml($fields['title']);
        }

        $lesson['free']        = empty($lesson['free']) ? 0 : 1;
        $lesson['number']      = $this->_getNextLessonNumber($lesson['courseId']);
        $lesson['seq']         = $this->_getNextCourseItemSeq($lesson['courseId']);
        $lesson['userId']      = $this->getCurrentUser()->id;
        $lesson['createdTime'] = time();

        if ($lesson['type'] == 'liveOpen') {
            $lesson['endTime']      = $lesson['startTime'] + $lesson['length'] * 60;
            $lesson['suggestHours'] = $lesson['length'] / 60;
        }

        $lesson = $this->getOpenCourseLessonDao()->addLesson($lesson);

        if (!empty($lesson['mediaId'])) {
            $this->getUploadFileService()->waveUploadFile($lesson['mediaId'], 'usedCount', 1);
        }

        $this->updateCourse($course['id'], array('lessonNum' => ($lesson['number'] - 1)));

        $this->getLogService()->info('openCourse', 'add_lesson', "添加公开课时《{$lesson['title']}》({$lesson['id']})", $lesson);
        $this->dispatchEvent("open.course.lesson.create", array('lesson' => $lesson));

        return $lesson;
    }

    public function updateLesson($courseId, $lessonId, $fields)
    {
        $argument = $fields;
        $course   = $this->getCourse($courseId);

        if (empty($course)) {
            throw $this->createServiceException("课程(#{$courseId})不存在！");
        }

        $lesson = $this->getCourseLesson($courseId, $lessonId);

        if (empty($lesson)) {
            throw $this->createServiceException("课时(#{$lessonId})不存在！");
        }

        $fields = ArrayToolkit::filter($fields, array(
            'title'         => '',
            'summary'       => '',
            'content'       => '',
            'media'         => array(),
            'mediaId'       => 0,
            'number'        => 0,
            'seq'           => 0,
            'chapterId'     => 0,
            'free'          => 0,
            'length'        => 0,
            'startTime'     => 0,
            'giveCredit'    => 0,
            'requireCredit' => 0,
            'homeworkId'    => 0,
            'exerciseId'    => 0,
            'testMode'      => 'normal',
            'testStartTime' => 0,
            'suggestHours'  => '1.0',
            'replayStatus'  => 'ungenerated',
            'status'        => 'unpublished'
        ));

        if (isset($fields['title'])) {
            $fields['title'] = $this->purifyHtml($fields['title']);
        }

        $fields['type'] = $lesson['type'];

        if ($fields['type'] == 'live' && isset($fields['startTime'])) {
            $fields['endTime']      = $fields['startTime'] + $fields['length'] * 60;
            $fields['suggestHours'] = $fields['length'] / 60;
        }

        if (array_key_exists('media', $fields)) {
            $this->fillLessonMediaFields($fields);
        }

        $updatedLesson = $this->getOpenCourseLessonDao()->updateLesson($lessonId, $fields);

        if (array_key_exists('mediaId', $fields)) {
            if ($fields['mediaId'] != $lesson['mediaId']) {
                if (!empty($fields['mediaId'])) {
                    $this->getUploadFileService()->waveUploadFile($fields['mediaId'], 'usedCount', 1);
                }

                if (!empty($lesson['mediaId'])) {
                    $this->getUploadFileService()->waveUploadFile($lesson['mediaId'], 'usedCount', -1);
                }
            }
        }

        return $updatedLesson;
    }

    public function deleteLesson($id)
    {
        $lesson = $this->getLesson($id);

        $result = $this->getOpenCourseLessonDao()->deleteLesson($id);

        $this->dispatchEvent("open.course.lesson.delete", array('lesson' => $lesson));

        return $result;
    }

    public function generateLessonReplay($courseId, $lessonId)
    {
        $lesson = $this->getLesson($lessonId);

        $client     = new EdusohoLiveClient();
        $replayList = $client->createReplayList($lesson["mediaId"], "录播回放", $lesson["liveProvider"]);

        if (isset($replayList['error']) && !empty($replayList['error'])) {
            return $replayList;
        }

        $this->getCourseLessonReplayDao()->deleteLessonReplayByLessonId($lessonId, 'openCourse');

        if (isset($replayList['data']) && !empty($replayList['data'])) {
            $replayList = json_decode($replayList["data"], true);
        }

        foreach ($replayList as $key => $replay) {
            $fields                = array();
            $fields["courseId"]    = $courseId;
            $fields["lessonId"]    = $lessonId;
            $fields["title"]       = $replay["subject"];
            $fields["replayId"]    = $replay["id"];
            $fields["userId"]      = $this->getCurrentUser()->id;
            $fields["createdTime"] = time();
            $courseLessonReplay    = $this->getCourseLessonReplayDao()->addCourseLessonReplay($fields);
        }

        $fields = array(
            "replayStatus" => "generated"
        );

        $lesson = $this->updateLesson($courseId, $lessonId, $fields);

        $this->dispatchEvent("course.lesson.generate.replay", $courseReplay);

        return $replayList;
    }

    public function getCourseLesson($courseId, $lessonId)
    {
        $lesson = $this->getOpenCourseLessonDao()->getLesson($lessonId);

        if (empty($lesson) || ($lesson['courseId'] != $courseId)) {
            return null;
        }

        return $lesson;
    }

    public function publishLesson($courseId, $lessonId)
    {
        $course = $this->tryManageOpenCourse($courseId);

        $lesson = $this->getCourseLesson($courseId, $lessonId);

        if (empty($lesson)) {
            throw $this->createServiceException("课时#{$lessonId}不存在");
        }

        $this->getOpenCourseLessonDao()->updateLesson($lesson['id'], array('status' => 'published'));
    }

    public function unpublishLesson($courseId, $lessonId)
    {
        $course = $this->tryManageOpenCourse($courseId);

        $lesson = $this->getCourseLesson($courseId, $lessonId);

        if (empty($lesson)) {
            throw $this->createServiceException("课时#{$lessonId}不存在");
        }

        $this->getOpenCourseLessonDao()->updateLesson($lesson['id'], array('status' => 'unpublished'));
    }

    public function sortCourseItems($courseId, array $itemIds)
    {
        $items          = $this->getCourseItems($courseId);
        $existedItemIds = array_keys($items);

        if (count($itemIds) != count($existedItemIds)) {
            throw $this->createServiceException('itemdIds参数不正确');
        }

        $diffItemIds = array_diff($itemIds, array_keys($items));

        if (!empty($diffItemIds)) {
            throw $this->createServiceException('itemdIds参数不正确');
        }

        $lessonNum = $seq = 0;

        foreach ($itemIds as $itemId) {
            $seq++;
            list($type) = explode('-', $itemId);
            $lessonNum++;

            $item   = $items[$itemId];
            $fields = array('number' => $lessonNum, 'seq' => $seq);

            if ($fields['number'] != $item['number'] || $fields['seq'] != $item['seq']) {
                $this->updateLesson($courseId, $item['id'], $fields);
            }
        }
    }

    /**
     * open_course_member
     */
    public function getMember($id)
    {
        return $this->getOpenCourseMemberDao()->getMember($id);
    }

    public function getCourseMember($courseId, $userId)
    {
        return $this->getOpenCourseMemberDao()->getCourseMember($courseId, $userId);
    }

    public function getCourseMemberByIp($courseId, $ip)
    {
        return $this->getOpenCourseMemberDao()->getCourseMemberByIp($courseId, $ip);
    }

    public function findMembersByCourseIds($courseIds)
    {
        return $this->getOpenCourseMemberDao()->findMembersByCourseIds($courseIds);
    }

    public function searchMemberCount($conditions)
    {
        return $this->getOpenCourseMemberDao()->searchMemberCount($conditions);
    }

    public function searchMembers($conditions, $orderBy, $start, $limit)
    {
        return $this->getOpenCourseMemberDao()->searchMembers($conditions, $orderBy, $start, $limit);
    }

    public function setCourseTeachers($courseId, $teachers)
    {
        // 过滤数据
        $teacherMembers = array();

        foreach (array_values($teachers) as $index => $teacher) {
            if (empty($teacher['id'])) {
                throw $this->createServiceException("教师ID不能为空，设置课程(#{$courseId})教师失败");
            }

            $user = $this->getUserService()->getUser($teacher['id']);

            if (empty($user)) {
                throw $this->createServiceException("用户不存在或没有教师角色，设置课程(#{$courseId})教师失败");
            }

            $teacherMembers[] = array(
                'courseId'    => $courseId,
                'userId'      => $user['id'],
                'role'        => 'teacher',
                'seq'         => $index,
                'isVisible'   => empty($teacher['isVisible']) ? 0 : 1,
                'createdTime' => time()
            );
        }

        // 先清除所有的已存在的教师学员
        $existTeacherMembers = $this->findCourseTeachers($courseId);

        foreach ($existTeacherMembers as $member) {
            $this->getOpenCourseMemberDao()->deleteMember($member['id']);
        }

        // 逐个插入新的教师的学员数据
        $visibleTeacherIds = array();

        foreach ($teacherMembers as $member) {
            // 存在学员信息，说明该用户先前是学生学员，则删除该学员信息。
            $existMember = $this->getCourseMember($courseId, $member['userId']);

            if ($existMember) {
                $this->getOpenCourseMemberDao()->deleteMember($existMember['id']);
            }

            $member = $this->getOpenCourseMemberDao()->addMember($member);

            if ($member['isVisible']) {
                $visibleTeacherIds[] = $member['userId'];
            }
        }

        $this->getLogService()->info('open_course', 'update_teacher', "更新课程#{$courseId}的教师", $teacherMembers);

        // 更新课程的teacherIds，该字段为课程可见教师的ID列表
        $fields = array('teacherIds' => $visibleTeacherIds);
        $course = $this->getOpenCourseDao()->updateCourse($courseId, $fields);
    }

    public function createMember($member)
    {
        $user = $this->getCurrentUser();

        if ($user->isLogin()) {
            $member['userId'] = $user['id'];
            $member['mobile'] = $user['verifiedMobile'];
        } else {
            $member['userId'] = 0;
        }

        $member['createdTime'] = time();

        $newMember = $this->getOpenCourseMemberDao()->addMember($member);

        $this->dispatchEvent("open.course.member.create", array('argument' => $member, 'newMember' => $newMember));

        return $newMember;
    }

    public function updateMember($id, $member)
    {
        return $this->getOpenCourseMemberDao()->updateMember($id, $member);
    }

    public function deleteMember($id)
    {
        return $this->getOpenCourseMemberDao()->deleteMember($id);
    }

    protected function fillLessonMediaFields(&$lesson)
    {
        if (in_array($lesson['type'], array('video', 'audio', 'ppt', 'document', 'flash'))) {
            $media = empty($lesson['media']) ? null : $lesson['media'];

            if (empty($media) || empty($media['source']) || empty($media['name'])) {
                throw $this->createServiceException("media参数不正确，添加课时失败！");
            }

            if ($media['source'] == 'self') {
                $media['id'] = intval($media['id']);

                if (empty($media['id'])) {
                    throw $this->createServiceException("media id参数不正确，添加/编辑课时失败！");
                }

                $file = $this->getUploadFileService()->getFile($media['id']);

                if (empty($file)) {
                    throw $this->createServiceException('文件不存在，添加/编辑课时失败！');
                }

                $lesson['mediaId']     = $file['id'];
                $lesson['mediaName']   = $file['filename'];
                $lesson['mediaSource'] = 'self';
                $lesson['mediaUri']    = '';
            } else {
                if (empty($media['uri'])) {
                    throw $this->createServiceException("media uri参数不正确，添加/编辑课时失败！");
                }

                $lesson['mediaId']     = 0;
                $lesson['mediaName']   = $media['name'];
                $lesson['mediaSource'] = $media['source'];
                $lesson['mediaUri']    = $media['uri'];
            }
        } elseif ($lesson['type'] == 'testpaper') {
            $lesson['mediaId'] = $lesson['mediaId'];
        } elseif ($lesson['type'] == 'live' || $lesson['type'] == 'liveOpen') {
        } else {
            $lesson['mediaId']     = 0;
            $lesson['mediaName']   = '';
            $lesson['mediaSource'] = '';
            $lesson['mediaUri']    = '';
        }

        unset($lesson['media']);

        return $lesson;
    }

    protected function _filterCourseFields($fields)
    {
        $fields = ArrayToolkit::filter($fields, array(
            'title'         => '',
            'subtitle'      => '',
            'about'         => '',
            'categoryId'    => 0,
            'tags'          => '',
            'startTime'     => 0,
            'endTime'       => 0,
            'locationId'    => 0,
            'address'       => '',
            'locked'        => 0,
            'hitNum'        => 0,
            'likeNum'       => 0,
            'postNum'       => 0,
            'status'        => 'draft',
            'lessonNum'     => 0,
            'smallPicture'  => '',
            'middlePicture' => '',
            'largePicture'  => '',
            'teacherIds'    => ''
        ));

        if (!empty($fields['about'])) {
            $fields['about'] = $this->purifyHtml($fields['about'], true);
        }

        if (!empty($fields['tags'])) {
            $fields['tags'] = explode(',', $fields['tags']);
            $fields['tags'] = $this->getTagService()->findTagsByNames($fields['tags']);
            array_walk($fields['tags'], function (&$item, $key) {
                $item = (int) $item['id'];
            }

            );
        }

        return $fields;
    }

    protected function hasOpenCourseManagerRole($courseId, $userId)
    {
        if ($this->getUserService()->hasAdminRoles($userId)) {
            return true;
        }

        $member = $this->getCourseMember($courseId, $userId);

        if ($member && ($member['role'] == 'teacher')) {
            return true;
        }

        return false;
    }

    private function _getNextLessonNumber($courseId)
    {
        $lessonCount = $this->searchLessonCount(array('courseId' => $courseId));
        return ($lessonCount + 1);
    }

    private function _getNextCourseItemSeq($courseId)
    {
        $lessonMaxSeq = $this->getOpenCourseLessonDao()->getLessonMaxSeqByCourseId($courseId);
        return $lessonMaxSeq + 1;
    }

    private function _deleteNotUsedPictures($course)
    {
        $oldPictures = array(
            'smallPicture'  => $course['smallPicture'] ? $course['smallPicture'] : null,
            'middlePicture' => $course['middlePicture'] ? $course['middlePicture'] : null,
            'largePicture'  => $course['largePicture'] ? $course['largePicture'] : null
        );

        $courseCount = $this->searchCourseCount(array('smallPicture' => $course['smallPicture']));

        if ($courseCount <= 1) {
            $fileService = $this->getFileService();
            array_map(function ($oldPicture) use ($fileService) {
                if (!empty($oldPicture)) {
                    $fileService->deleteFileByUri($oldPicture);
                }
            }, $oldPictures);
        }
    }

    private function findCourseTeachers($courseId)
    {
        return $this->getOpenCourseMemberDao()->findMembersByCourseIdAndRole($courseId, 'teacher', 0, 100);
    }

    protected function getUploadFileService()
    {
        return $this->createService('File.UploadFileService');
    }

    protected function getOpenCourseDao()
    {
        return $this->createDao('OpenCourse.OpenCourseDao');
    }

    protected function getOpenCourseLessonDao()
    {
        return $this->createDao('OpenCourse.OpenCourseLessonDao');
    }

    protected function getOpenCourseMemberDao()
    {
        return $this->createDao('OpenCourse.OpenCourseMemberDao');
    }

    protected function getFavoriteDao()
    {
        return $this->createDao('Course.FavoriteDao');
    }

    protected function getCourseLessonReplayDao()
    {
        return $this->createDao('Course.CourseLessonReplayDao');
    }

    protected function getLogService()
    {
        return $this->createService('System.LogService');
    }

    protected function getUserService()
    {
        return $this->createService('User.UserService');
    }

    protected function getFileService()
    {
        return $this->createService('Content.FileService');
    }
}
