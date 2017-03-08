<?php

namespace Biz\Course\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Biz\BaseService;
use Biz\Content\Service\FileService;
use Biz\Course\Copy\Impl\ClassroomCourseCopy;
use Biz\Course\Dao\CourseDao;
use Biz\Course\Dao\CourseSetDao;
use Biz\Course\Dao\FavoriteDao;
use Biz\Course\Service\CourseDeleteService;
use Biz\Course\Service\CourseNoteService;
use Biz\Course\Service\CourseService;
use Biz\Course\Service\CourseSetService;
use Biz\Course\Service\MaterialService;
use Biz\Course\Service\MemberService;
use Biz\Course\Service\ReviewService;
use Biz\System\Service\LogService;
use Biz\Taxonomy\Service\TagService;
use Biz\User\Service\UserService;
use Codeages\Biz\Framework\Event\Event;
use Biz\Course\Service\CourseNoteService;
use Biz\Course\Service\CourseDeleteService;
use Biz\Course\Copy\Impl\ClassroomCourseCopy;

class CourseSetServiceImpl extends BaseService implements CourseSetService
{
    public function findCourseSetsByParentIdAndLocked($parentId, $locked)
    {
        return $this->getCourseSetDao()->findCourseSetsByParentIdAndLocked($parentId, $locked);
    }

    public function recommendCourse($id, $number)
    {
        $this->tryManageCourseSet($id);
        if (!is_numeric($number)) {
            throw $this->createAccessDeniedException('recmendNum should be number!');
        }
        $course = $this->getCourseSetDao()->update(
            $id,
            array(
                'recommended' => 1,
                'recommendedSeq' => (int) $number,
                'recommendedTime' => time(),
            )
        );

        $this->getLogService()->info('course', 'recommend', "推荐课程《{$course['title']}》(#{$course['id']}),序号为{$number}");

        return $course;
    }

    public function cancelRecommendCourse($id)
    {
        $course = $this->tryManageCourseSet($id);

        $this->getCourseSetDao()->update(
            $id,
            array(
                'recommended' => 0,
                'recommendedTime' => 0,
                'recommendedSeq' => 0,
            )
        );

        $this->getLogService()->info('course', 'cancel_recommend', "取消推荐课程《{$course['title']}》(#{$course['id']})");
    }

    public function favorite($id)
    {
        $courseSet = $this->getCourseSet($id);
        $user = $this->getCurrentUser();

        if (empty($courseSet)) {
            return false;
        }

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('user is not login');
        }

        $isFavorite = $this->isUserFavorite($user['id'], $courseSet['id']);

        if ($isFavorite) {
            return true;
        }

        $course = $this->getCourseService()->getFirstPublishedCourseByCourseSetId($courseSet['id']);

        if (empty($course)) {
            return false;
        }

        $favorite = array(
            'courseSetId' => $courseSet['id'],
            'type' => 'course',
            'userId' => $user['id'],
            'courseId' => $course['id'],
        );

        $favorite = $this->getFavoriteDao()->create($favorite);

        return !empty($favorite);
    }

    public function unfavorite($id)
    {
        $courseSet = $this->getCourseSet($id);
        $user = $this->getCurrentUser();

        if (empty($courseSet)) {
            return false;
        }

        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('user is not log in');
        }

        $favorite = $this->getFavoriteDao()->getByUserIdAndCourseSetId($user['id'], $courseSet['id'], 'course');

        if (empty($favorite)) {
            return true;
        }

        $this->getFavoriteDao()->delete($favorite['id']);

        return true;
    }

    public function isUserFavorite($userId, $courseSetId)
    {
        $courseSet = $this->getCourseSet($courseSetId);
        $favorite = $this->getFavoriteDao()->getByUserIdAndCourseSetId($userId, $courseSet['id'], 'course');

        return !empty($favorite);
    }

    public function tryManageCourseSet($id)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            throw $this->createAccessDeniedException('Unauthorized');
        }

        $courseSet = $this->getCourseSetDao()->get($id);

        if (empty($courseSet)) {
            throw $this->createNotFoundException("CourseSet#{$id} Not Found");
        }

        if (!$this->hasCourseSetManageRole($id)) {
            throw $this->createAccessDeniedException('Unauthorized');
        }

        return $courseSet;
    }

    public function hasCourseSetManageRole($courseSetId = 0)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            return false;
        }

        if ($this->hasAdminRole()) {
            return true;
        }

        if (empty($courseSetId)) {
            return $user->isTeacher();
        }

        $courseSet = $this->getCourseSetDao()->get($courseSetId);
        if (empty($courseSet)) {
            return false;
        }

        if ($courseSet['creator'] == $user->getId()) {
            return true;
        }

        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSetId);
        foreach ($courses as $course) {
            if (in_array($user->getId(), $course['teacherIds'])) {
                $this->getCourseService()->hasCourseManagerRole($course['id']);

                return true;
            }
        }

        return false;
    }

    protected function hasAdminRole()
    {
        $user = $this->getCurrentUser();

        return $user->hasPermission('admin_course_content_manage');
    }

    public function searchCourseSets(array $conditions, $orderBys, $start, $limit)
    {
        $orderBys = $this->getOrderBys($orderBys);
        $preparedCondtions = $this->prepareConditions($conditions);

        return $this->getCourseSetDao()->search($preparedCondtions, $orderBys, $start, $limit);
    }

    public function countCourseSets(array $conditions)
    {
        return $this->getCourseSetDao()->count($conditions);
    }

    public function countUserLearnCourseSets($userId)
    {
        $courses = $this->getCourseService()->findLearnCoursesByUserId($userId);
        $courseSets = $this->findCourseSetsByCourseIds(ArrayToolkit::column($courses, 'id'));

        return count($courseSets);
    }

    public function searchUserLearnCourseSets($userId, $start, $limit)
    {
        $sets = $this->findLearnCourseSetsByUserId($userId);
        $ids = ArrayToolkit::column($sets, 'id');

        if (empty($ids)) {
            return array();
        }

        return $this->searchCourseSets(
            array(
                'ids' => $ids,
                'status' => 'published',
                'parentId' => 0,
            ),
            array(
                'createdTime' => 'DESC',
            ),
            $start,
            $limit
        );
    }

    public function countUserTeachingCourseSets($userId, array $conditions)
    {
        $members = $this->getCourseMemberService()->findTeacherMembersByUserId($userId);
        $ids = ArrayToolkit::column($members, 'courseSetId');

        if (empty($ids)) {
            return 0;
        }

        $conditions = array_merge($conditions, array('ids' => $ids));

        return $this->countCourseSets($conditions);
    }

    public function searchUserTeachingCourseSets($userId, array $conditions, $start, $limit)
    {
        $members = $this->getCourseMemberService()->findTeacherMembersByUserId($userId);
        $ids = ArrayToolkit::column($members, 'courseSetId');

        if (empty($ids)) {
            return array();
        }

        $conditions = array_merge($conditions, array('ids' => $ids));

        return $this->searchCourseSets($conditions, array('createdTime' => 'DESC'), $start, $limit);
    }

    public function findCourseSetsByCourseIds(array $courseIds)
    {
        $courses = $this->getCourseService()->findCoursesByIds($courseIds);
        $courseSetIds = ArrayToolkit::column($courses, 'courseSetId');

        return $this->findCourseSetsByIds($courseSetIds);
    }

    public function findCourseSetsByIds(array $ids)
    {
        $courseSets = $this->getCourseSetDao()->findByIds($ids);

        return ArrayToolkit::index($courseSets, 'id');
    }

    public function findCourseSetsLikeTitle($title)
    {
        return $this->getCourseSetDao()->findLikeTitle($title);
    }

    public function getCourseSet($id)
    {
        return $this->getCourseSetDao()->get($id);
    }

    public function createCourseSet($courseSet)
    {
        if (!ArrayToolkit::requireds($courseSet, array('title', 'type'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }

        if (!in_array($courseSet['type'], array('normal', 'live', 'liveOpen', 'open'))) {
            throw $this->createInvalidArgumentException('Invalid Param: type');
        }

        if (!$this->hasCourseSetManageRole()) {
            throw $this->createAccessDeniedException('You have no access to Course Set Management');
        }

        $courseSet = ArrayToolkit::parts(
            $courseSet,
            array(
                'type',
                'title',
            )
        );
        $courseSet['status'] = 'draft';
        $courseSet['creator'] = $this->getCurrentUser()->getId();
        $created = $this->getCourseSetDao()->create($courseSet);

        // 同时创建默认的教学计划
        // XXX
        // 1. 是否创建默认教学计划应该是可配的；
        // 2. 教学计划的内容（主要是学习模式、有效期模式）也应该是可配的
        $defaultCourse = $this->generateDefaultCourse($created);

        $this->getCourseService()->createCourse($defaultCourse);

        return $created;
    }

    public function copyCourseSet($classroomId, $courseSetId, $courseId)
    {
        //$courseSet = $this->tryManageCourseSet($courseSetId);
        $courseSet = $this->getCourseSet($courseSetId);
        $entityCopy = new ClassroomCourseCopy($this->biz);

        $newCourse = $entityCopy->copy($courseSet, array('courseId' => $courseId, 'classroomId' => $classroomId));

        $this->dispatchEvent(
            'classroom.course.copy',
            new Event(
                $newCourse,
                array('classroomId' => $classroomId, 'courseSetId' => $courseSetId, 'courseId' => $courseId)
            )
        );

        return $newCourse;
    }

    public function updateCourseSet($id, $fields)
    {
        if (!ArrayToolkit::requireds($fields, array('title', 'categoryId', 'serializeMode'))) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }
        if (!in_array($fields['serializeMode'], array('none', 'serialized', 'finished'))) {
            throw $this->createInvalidArgumentException('Invalid Param: serializeMode');
        }

        $courseSet = $this->tryManageCourseSet($id);

        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'title',
                'subtitle',
                'tags',
                'categoryId',
                'serializeMode',
                // 'summary',
                'smallPicture',
                'middlePicture',
                'largePicture',
                'teacherIds',
            )
        );

        if (!empty($fields['tags'])) {
            $fields['tags'] = explode(',', $fields['tags']);
            $fields['tags'] = $this->getTagService()->findTagsByNames($fields['tags']);
            $fields['tags'] = ArrayToolkit::column($fields['tags'], 'id');
        }

        $fields = array_filter($fields);

        $this->updateCourseSerializeMode($courseSet, $fields);
        if (empty($fields['subtitle'])) {
            $fields['subtitle'] = null;
        }

        $courseSet = $this->getCourseSetDao()->update($courseSet['id'], $fields);

        $this->dispatchEvent('course-set.update', new Event($courseSet));

        return $courseSet;
    }

    protected function updateCourseSerializeMode($courseSet, $fields)
    {
        if (isset($fields['serializeMode']) && $fields['serializeMode'] !== $courseSet['serializeMode']) {
            $courses = $this->getCourseDao()->findByCourseSetIds(array($courseSet['id']));
            foreach ($courses as $course) {
                $this->getCourseService()->updateCourse(
                    $course['id'],
                    array('serializeMode' => $fields['serializeMode'])
                );
            }
        }
    }

    public function updateCourseSetDetail($id, $fields)
    {
        $courseSet = $this->tryManageCourseSet($id);

        $fields = ArrayToolkit::parts(
            $fields,
            array(
                'summary',
                'goals',
                'audiences',
            )
        );

        $courseSet = $this->getCourseSetDao()->update($courseSet['id'], $fields);
        $this->dispatchEvent('course-set.update', new Event($courseSet));

        return $courseSet;
    }

    public function changeCourseSetCover($id, $coverArray)
    {
        if (empty($coverArray)) {
            throw $this->createInvalidArgumentException('Invalid Param: cover');
        }
        $courseSet = $this->tryManageCourseSet($id);
        $covers = array();
        foreach ($coverArray as $cover) {
            $file = $this->getFileService()->getFile($cover['id']);
            $covers[$cover['type']] = $file['uri'];
        }

        $courseSet = $this->getCourseSetDao()->update($courseSet['id'], array('cover' => $covers));
        $this->dispatchEvent('course-set.update', new Event($courseSet));

        return $courseSet;
    }

    public function deleteCourseSet($id)
    {
        $courseSet = $this->tryManageCourseSet($id);
        $subCourseSets = $this->getCourseSetDao()->findCourseSetsByParentIdAndLocked($id, 1);
        if (!empty($subCourseSets)) {
            throw $this->createAccessDeniedException('该课程在班级下引用，请先删除引用课程！');
        }

        return $this->getCourseDeleteService()->deleteCourseSet($courseSet['id']);
    }

    public function findTeachingCourseSetsByUserId($userId, $onlyPublished = true)
    {
        $courses = $this->getCourseService()->findTeachingCoursesByUserId($userId, $onlyPublished);
        $setIds = ArrayToolkit::column($courses, 'courseSetId');

        if ($onlyPublished) {
            return $this->findPublicCourseSetsByIds($setIds);
        } else {
            return $this->findCourseSetsByIds($setIds);
        }
    }

    public function findLearnCourseSetsByUserId($userId)
    {
        $courses = $this->getCourseService()->findLearnCoursesByUserId($userId);
        $setIds = ArrayToolkit::column($courses, 'courseSetId');

        return $this->findPublicCourseSetsByIds($setIds);
    }

    public function findPublicCourseSetsByIds(array $ids)
    {
        if (empty($ids)) {
            return array();
        }

        $conditions = array(
            'ids' => $ids,
            'status' => 'published',
        );
        $count = $this->countCourseSets($conditions);

        return $this->searchCourseSets($conditions, array('createdTime' => 'DESC'), 0, $count);
    }

    public function updateCourseSetStatistics($id, array $fields)
    {
        if (empty($fields)) {
            throw $this->createInvalidArgumentException('Invalid Arguments');
        }

        $updateFields = array();
        foreach ($fields as $field) {
            if ($field === 'ratingNum') {
                $ratingFields = $this->getReviewService()->countRatingByCourseSetId($id);
                $updateFields = array_merge($updateFields, $ratingFields);
            } elseif ($field === 'noteNum') {
                $noteNum = $this->getNoteService()->countCourseNoteByCourseSetId($id);
                $updateFields['noteNum'] = $noteNum;
            } elseif ($field === 'studentNum') {
                $updateFields['studentNum'] = $this->countStudentNumById($id);
            } elseif ($field === 'materialNum') {
                $updateFields['materialNum'] = $this->getCourseMaterialService()->countMaterials(
                    array('courseSetId' => $id, 'source' => 'coursematerial')
                );
            }
        }

        $courseSet = $this->getCourseSetDao()->update($id, $updateFields);
        $this->dispatchEvent('course-set.update', new Event($courseSet));

        return $courseSet;
    }

    public function publishCourseSet($id)
    {
        $courseSet = $this->tryManageCourseSet($id);
        $courseSet = $this->getCourseSetDao()->update($courseSet['id'], array('status' => 'published'));
        $this->dispatchEvent('course-set.publish', new Event($courseSet));
    }

    public function closeCourseSet($id)
    {
        $courseSet = $this->tryManageCourseSet($id);
        if ($courseSet['status'] != 'published') {
            throw $this->createAccessDeniedException('CourseSet has not bean published');
        }
        $courseSet = $this->getCourseSetDao()->update($courseSet['id'], array('status' => 'closed'));
        $this->dispatchEvent('course-set.closed', new Event($courseSet));
    }

    public function countUserFavorites($userId)
    {
        return $this->getFavoriteDao()->countByUserId($userId);
    }

    public function searchUserFavorites($userId, $start, $limit)
    {
        return $this->getFavoriteDao()->searchByUserId($userId, $start, $limit);
    }

    /**
     * 根据排序规则返回排序数组.
     */
    protected function getOrderBys($order)
    {
        if (is_array($order)) {
            return $order;
        }

        $typeOrderByMap = array(
            'hitNum' => array('hitNum' => 'DESC'),
            'recommended' => array('recommendedTime' => 'DESC'),
            'rating' => array('rating' => 'DESC'),
            'studentNum' => array('studentNum' => 'DESC'),
            'recommendedSeq' => array('recommendedSeq' => 'ASC'),
        );
        if (isset($typeOrderByMap[$order])) {
            return $typeOrderByMap[$order];
        } else {
            return array('createdTime' => 'DESC');
        }
    }

    public function findCourseSetIncomesByCourseSetIds(array $courseSetIds)
    {
        return $this->getCourseDao()->findCourseSetIncomesByCourseSetIds($courseSetIds);
    }

    public function batchUpdateOrg($courseSetIds, $orgCode)
    {
        if (!is_array($courseSetIds)) {
            $courseSetIds = array($courseSetIds);
        }

        $fields = $this->fillOrgId(array('orgCode' => $orgCode));

        foreach ($courseSetIds as $courseSetId) {
            $this->getCourseSetDao()->update($courseSetId, $fields);
        }
    }

    public function unlockCourseSet($id)
    {
        $courseSet = $this->tryManageCourseSet($id);
        if ($courseSet['parentId'] <= 0 || $courseSet['locked'] == 0) {
            throw $this->createAccessDeniedException('Invalid Operation');
        }
        $courses = $this->getCourseService()->findCoursesByCourseSetId($id);
        try {
            $this->beginTransaction();
            $courseSet = $this->getCourseSetDao()->update($id, array('locked' => 0));
            $this->getCourseDao()->update($courses[0]['id'], array('locked' => 0));
            $this->commit();

            return $courseSet;
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function analysisCourseSetDataByTime($startTime, $endTime)
    {
        return $this->getCourseSetDao()->analysisCourseSetDataByTime($startTime, $endTime);
    }

    public function updateCourseSetMinAndMaxPublishedCoursePrice($courseSetId)
    {
        $price = $this->getCourseService()->getMinAndMaxPublishedCoursePriceByCourseSetId($courseSetId);

        return $this->getCourseSetDao()->update(
            $courseSetId,
            array('minCoursePrice' => $price['minPrice'], 'maxCoursePrice' => $price['maxPrice'])
        );
    }

    public function updateMaxRate($id, $maxRate)
    {
        $courseSet = $this->getCourseSetDao()->update($id, array('maxRate' => $maxRate));
        $this->dispatchEvent(
            'courseSet.maxRate.update',
            new Event(array('courseSet' => $courseSet, 'maxRate' => $maxRate))
        );

        return $courseSet;
    }

    protected function validateCourseSet($courseSet)
    {
        if (!ArrayToolkit::requireds($courseSet, array('title', 'type'))) {
            throw $this->createInvalidArgumentException('Lack of Required Fields');
        }
        if (!in_array($courseSet['type'], array('normal', 'live', 'liveOpen', 'open'))) {
            throw $this->createInvalidArgumentException('Invalid Param: type');
        }
    }

    protected function prepareConditions($conditions)
    {
        array_filter(
            $conditions,
            function ($value) {
                if (is_numeric($value)) {
                    return true;
                }

                return !empty($value);
            }
        );

        if (!empty($conditions['creatorName'])) {
            $user = $this->getUserService()->getUserByNickname($conditions['creatorName']);
            $conditions['creator'] = $user ? $user['id'] : -1;
        }

        return $conditions;
    }

    protected function countStudentNumById($id)
    {
        $courseSet = $this->getCourseSet($id);
        $courses = $this->getCourseService()->findCoursesByCourseSetId($courseSet['id']);

        return array_reduce(
            $courses,
            function ($studentNum, $course) {
                $studentNum += $course['studentNum'];

                return $studentNum;
            }
        );
    }

    /**
     * @return CourseSetDao
     */
    protected function getCourseSetDao()
    {
        return $this->createDao('Course:CourseSetDao');
    }

    /**
     * @return CourseDao
     */
    protected function getCourseDao()
    {
        return $this->createDao('Course:CourseDao');
    }

    /**
     * @return CourseNoteService
     */
    protected function getNoteService()
    {
        return $this->createService('Course:CourseNoteService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->biz->service('Course:MemberService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->biz->service('Course:CourseService');
    }

    /**
     * @return TagService
     */
    protected function getTagService()
    {
        return $this->biz->service('Taxonomy:TagService');
    }

    /**
     * @return ReviewService
     */
    protected function getReviewService()
    {
        return $this->biz->service('Course:ReviewService');
    }

    /**
     * @return FileService
     */
    protected function getFileService()
    {
        return $this->biz->service('Content:FileService');
    }

    /**
     * @return FavoriteDao
     */
    protected function getFavoriteDao()
    {
        return $this->biz->dao('Course:FavoriteDao');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return MaterialService
     */
    protected function getCourseMaterialService()
    {
        return $this->createService('Course:MaterialService');
    }

    /**
     * @return CourseDeleteService
     */
    protected function getCourseDeleteService()
    {
        return $this->createService('Course:CourseDeleteService');
    }

    protected function generateDefaultCourse($created)
    {
        $defaultCourse = array(
            'courseSetId' => $created['id'],
            'title' => '默认教学计划',
            'expiryMode' => 'days',
            'expiryDays' => 0,
            'learnMode' => 'freeMode',
            'isDefault' => 1,
            'isFree' => 1,
            'serializeMode' => $created['serializeMode'],
            'status' => 'draft',
        );

        return $defaultCourse;
    }
}