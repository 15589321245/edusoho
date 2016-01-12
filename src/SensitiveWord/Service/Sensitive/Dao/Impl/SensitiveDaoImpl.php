<?php
namespace SensitiveWord\Service\Sensitive\Dao\Impl;


use SensitiveWord\Service\Sensitive\Dao\SensitiveDao;
use Topxia\Service\Common\BaseDao;

class SensitiveDaoImpl extends BaseDao implements SensitiveDao
{
    protected $table = 'keyword';

    public function getKeyword($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        return $this->getConnection()->fetchAssoc($sql, array($id)) ? : null;
    }

     public function getKeywordByName($name)
     {
         $sql = "SELECT * FROM {$this->table} WHERE name = ? LIMIT 1";
         return $this->getConnection()->fetchAssoc($sql, array($name)) ? : null;
     }

    public function findAllKeywords()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY createdTime DESC";
        return $this->getConnection()->fetchAll($sql, array());
    }

    public function addKeyword(array $fields)
    {
        $affected = $this->getConnection()->insert($this->table, $fields);
        if ($affected <= 0) {
            throw $this->createDaoException('Insert keyword error.');
        }
        return $this->getKeyword($this->getConnection()->lastInsertId());
    }

    public function deleteKeyword($id)
    {
        return $this->getConnection()->delete($this->table, array('id' => $id));
    }

    public function searchkeywordsCount()
    {   
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        return $this->getConnection()->fetchColumn($sql) ? : null;
    }

    public function searchKeywords($conditions, $orderBy, $start, $limit)
    {

        $this->filterStartLimit($start, $limit);
        $builder = $this->createUserQueryBuilder($conditions)
            ->select('*')
            ->orderBy($orderBy[0], $orderBy[1])
            ->setFirstResult($start)
            ->setMaxResults($limit);
        return $builder->execute()->fetchAll() ? : array();
    }
    //     $sql = "SELECT * FROM {$this->table} ORDER BY createdTime DESC LIMIT {$start},{$limit}";
    //     return $this->getConnection()->fetchAll($sql)?:null;
    // }

    public function waveBannedNum($id, $diff)
    {
        
        $sql = "UPDATE {$this->table} SET bannedNum = bannedNum + ? WHERE id = ? LIMIT 1";
        return $this->getConnection()->executeQuery($sql, array($diff, $id));
    }

    protected function createUserQueryBuilder($conditions)
    {
        $conditions = array_filter($conditions,function($v){
            if($v === 0){
                return true;
            }
                
            if(empty($v)){
                return false;
            }
            return true;
        });
        if (isset($conditions['keyword'])) {
            if($conditions['searchKeyWord'] == 'id') {
                $conditions['id'] = $conditions['keyword'];
            }
            else if ($conditions['searchKeyWord'] == 'name') {
                $conditions['name'] = "%{$conditions['keyword']}%";
            }
        }
        
        return  $this->createDynamicQueryBuilder($conditions)
            ->from($this->table, 'keyword')
            ->andWhere('id = :id')
            ->andWhere('state = :state')
            ->andWhere('UPPER(name) LIKE :name');
    }
}
