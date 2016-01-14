<?php
namespace SensitiveWord\Service\Sensitive;

interface SensitiveService {
    /**
     * @param $text
     * @return mixed
     */
    public function scanText($text);

    public function sensitiveCheck($text, $type = '');
    
    public function findAllKeywords();
    
    public function addKeyword($keyword, $state);
    
    public function deleteKeyword($id);

    public function updateKeyword($id, $conditions);
    
    public function searchkeywordsCount();
    
    public function searchKeywords($conditions, $orderBy,$start, $limit);
    
    public function searchBanlogsCount($conditions);
    
    public function searchBanlogs($conditions, $orderBy, $start, $limit);
}
