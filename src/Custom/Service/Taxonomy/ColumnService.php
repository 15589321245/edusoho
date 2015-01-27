<?php
namespace Custom\Service\Taxonomy;

interface ColumnService
{
    public function getColumn($id);

    public function getColumnByCode($code);

    public function getColumnByName($name);

    public function getColumnByLikeName($name);

    public function findAllColumns($start, $limit);

    public function getAllColumnCount();

    public function searchColumns(array $conditions, array $orderBy, $start, $limit);

    public function searchColumnCount(array $conditions);



    public function isColumnNameAvalieable($name, $exclude=null);

    public function addColumn(array $column);

    public function updateColumn($id, array $fields);

    public function deleteColumn($id);

    public function changeColumnAvatar($columnId, $filePath, array $options);

    public function findColumnsByIds(array $ids);

    public function findTagIdsByColumnIdAndCourseComplexity($columId,$courseComplexity);
       
}


