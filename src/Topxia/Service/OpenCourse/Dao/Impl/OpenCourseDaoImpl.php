<?php

namespace Topxia\Service\OpenCourse\Dao\Impl;

use Topxia\Service\Common\BaseDao;
use Topxia\Service\OpenCourse\Dao\OpenCourseDao;

class OpenCourseDaoImpl extends BaseDao implements OpenCourseDao
{
    protected $table = 'open_course';

    private $serializeFields = array(
        'teacherIds' => 'saw',
        'tags'       => 'saw'
    );

    public function getCourse($id)
    {
        $that = $this;

        return $this->fetchCached("id:{$id}", $id, function ($id) use ($that) {
            $sql    = "SELECT * FROM {$that->getTable()} WHERE id = ? LIMIT 1";
            $course = $that->getConnection()->fetchAssoc($sql, array($id));

            return $course ? $this->createSerializer()->unserialize($course, $this->serializeFields) : null;
        });
    }

    public function findCoursesByIds(array $ids)
    {
        if (empty($ids)) {
            return array();
        }

        $marks   = str_repeat('?,', count($ids) - 1).'?';
        $sql     = "SELECT * FROM {$this->getTable()} WHERE id IN ({$marks});";
        $courses = $this->getConnection()->fetchAll($sql, $ids);

        return $courses ? $this->createSerializer()->unserializes($courses, $this->serializeFields) : null;
    }

    public function searchCourses($conditions, $orderBy, $start, $limit)
    {
        $this->filterStartLimit($start, $limit);
        $orderBy = $this->checkOrderBy($orderBy, array('createdTime'));

        $builder = $this->_createSearchQueryBuilder($conditions)
            ->select('*')
            ->orderBy($orderBy[0], $orderBy[1])
            ->setFirstResult($start)
            ->setMaxResults($limit);

        $courses = $builder->execute()->fetchAll();
        return $courses ? $this->createSerializer()->unserializes($courses, $this->serializeFields) : array();
    }

    public function searchCourseCount($conditions)
    {
        $builder = $this->_createSearchQueryBuilder($conditions)
            ->select('COUNT(id)');
        return $builder->execute()->fetchColumn(0);
    }

    public function addCourse($course)
    {
        $course = $this->createSerializer()->serialize($course, $this->serializeFields);

        $course['createdTime'] = time();
        $course['updatedTime'] = $course['createdTime'];
        $affected              = $this->getConnection()->insert($this->table, $course);
        $this->clearCached();

        if ($affected <= 0) {
            throw $this->createDaoException('Insert course error.');
        }

        return $this->getCourse($this->getConnection()->lastInsertId());
    }

    public function updateCourse($id, $fields)
    {
        $fields['updatedTime'] = time();
        $this->getConnection()->update($this->table, $fields, array('id' => $id));
        $this->clearCached();
        return $this->getCourse($id);
    }

    public function deleteCourse($id)
    {
        $result = $this->getConnection()->delete($this->table, array('id' => $id));
        $this->clearCached();
        return $result;
    }

    public function waveCourse($id, $field, $diff)
    {
        $fields = array('hitNum', 'lessonNum');

        if (!in_array($field, $fields)) {
            throw \InvalidArgumentException(sprintf("%s字段不允许增减，只有%s才被允许增减", $field, implode(',', $fields)));
        }

        $currentTime = time();

        $sql = "UPDATE {$this->table} SET {$field} = {$field} + ?, updatedTime = '{$currentTime}' WHERE id = ? LIMIT 1";

        $result = $this->getConnection()->executeQuery($sql, array($diff, $id));
        $this->clearCached();
        return $result;
    }

    protected function _createSearchQueryBuilder($conditions)
    {
        if (isset($conditions['title'])) {
            $conditions['titleLike'] = "%{$conditions['title']}%";
            unset($conditions['title']);
        }

        if (isset($conditions['tagId'])) {
            $tagId = (int) $conditions['tagId'];

            if (!empty($tagId)) {
                $conditions['tagsLike'] = "%|{$conditions['tagId']}|%";
            }

            unset($conditions['tagId']);
        }

        if (empty($conditions['status']) || $conditions['status'] == "") {
            unset($conditions['status']);
        }

        $builder = $this->createDynamicQueryBuilder($conditions)
            ->from($this->table, 'course')
            ->andWhere('updatedTime >= :updatedTime_GE')
            ->andWhere('status = :status')
            ->andWhere('type = :type')
            ->andWhere('title LIKE :titleLike')
            ->andWhere('userId = :userId')
            ->andWhere('tags LIKE :tagsLike')
            ->andWhere('startTime >= :startTimeGreaterThan')
            ->andWhere('startTime < :startTimeLessThan')
            ->andWhere('rating > :ratingGreaterThan')
            ->andWhere('createdTime >= :startTime')
            ->andWhere('createdTime <= :endTime')
            ->andWhere('categoryId = :categoryId')
            ->andWhere('smallPicture = :smallPicture')
            ->andWhere('categoryId IN ( :categoryIds )')
            ->andWhere('parentId = :parentId')
            ->andWhere('parentId > :parentId_GT')
            ->andWhere('parentId IN ( :parentIds )')
            ->andWhere('id NOT IN ( :excludeIds )')
            ->andWhere('id IN ( :courseIds )')
            ->andWhere('locked = :locked');

        if (isset($conditions['tagIds'])) {
            $tagIds = $conditions['tagIds'];

            foreach ($tagIds as $key => $tagId) {
                $conditions['tagIds_'.$key] = '%|'.$tagId.'|%';
                $builder->andWhere('tags LIKE :tagIds_'.$key);
            }

            unset($conditions['tagIds']);
        }

        return $builder;
    }
}
