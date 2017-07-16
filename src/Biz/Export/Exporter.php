<?php

namespace Biz\Export;

use Codeages\Biz\Framework\Context\Biz;

abstract class Exporter implements ExporterInterface
{
    protected $biz;
    protected $conditions;

    final public function __construct(Biz $biz, $conditions)
    {
        $this->biz = $biz;
        $this->conditions = $conditions;
    }

    abstract public function getTitles();

    abstract public function getExportContent($start, $limit);

    abstract public function canExport();

    public function getPreResult($fileName)
    {
        list($start, $limit, $exportAllowCount) = $this->getPageConditions();

        if (empty($this->conditions['start'])) {
            $filePath = $this->addFileTitle($fileName);
        } else {
            //第一次回调是文件名，第二次回调就是路径了
            $filePath = $this->conditions['fileName'];
        }

        list($data, $count) = $this->getExportContent(
            $start,
            $limit
        );

        $content = implode("\r\n", $data);
        file_put_contents($filePath, $content."\r\n", FILE_APPEND);

        $endPage = $start + $limit;
        $endStatus = ($endPage >= $count) || ($endPage > $exportAllowCount);

        $status = $endStatus ? 'export' : 'getData';

        return array(
            'status' => $status,
            'filePath' => $filePath,
            'start' => $endPage,
        );
    }

    protected function handleTitle()
    {
        // todu 国际化，转译
        $titles = $this->getTitles();
        foreach ($titles as $key => $value){
            $titles[$key] = '"'.str_replace('""','"',$value) .'"';
        }
        return $titles;
    }

    protected function addFileTitle($fileName)
    {
        $title = $this->handleTitle();

        $content = implode(',' ,$title);

        if (empty($this->conditions['filePath'])){
            $rootPath = $this->biz['topxia.upload.private_directory'];
            $user = $this->biz['user'];
            $filePath = $rootPath.'/export_content_'.$fileName.'_'.$user->getId().time().'.txt';
        } else {
            $filePath =  $this->conditions['filePath'];
        }

        file_put_contents($filePath, $content."\r\n", FILE_APPEND);

        return $filePath;
    }

    protected function getPageConditions()
    {
        $magic = $this->getSettingService()->get('magic');
        $start = isset($this->conditions['start']) ? $this->conditions['start'] : 0;
        if (empty($magic['export_limit'])) {
            $magic['export_limit'] = 1000;
        }

        if (empty($magic['export_allow_count'])) {
            $magic['export_allow_count'] = 10000;
        }

        $limit = ($magic['export_limit'] > $magic['export_allow_count']) ? $magic['export_allow_count'] : $magic['export_limit'];

        return array($start, $limit, $magic['export_allow_count']);
    }
}