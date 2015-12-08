<?php
namespace Topxia\WebBundle\Controller;

use Topxia\Common\Paginator;
use Topxia\Common\FileToolkit;
use Topxia\Common\ArrayToolkit;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Service\CloudPlatform\CloudAPIFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CourseFileManageController extends BaseController
{
    public function indexAction(Request $request, $id)
    {
        $course = $this->getCourseService()->tryManageCourse($id);

        $type = $request->query->get('type');
        $type = in_array($type, array('courselesson', 'coursematerial')) ? $type : 'courselesson';

        $conditions = array(
            'targetType' => $type,
            'targetId'   => $course['id']
        );

        if (array_key_exists('targetId', $conditions) && !empty($conditions['targetId'])) {
            $course = $this->getCourseService()->getCourse($conditions['targetId']);

            if ($course['parentId'] > 0 && $course['locked'] == 1) {
                $conditions['targetId'] = $course['parentId'];
            }
        }

        $paginator = new Paginator(
            $request,
            $this->getUploadFileService()->searchFileCount($conditions),
            20
        );

        $files = $this->getUploadFileService()->searchFiles(
            $conditions,
            'latestCreated',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        foreach ($files as $key => $file) {
            $files[$key]['metas2'] = json_decode($file['metas2'], true) ?: array();

            $files[$key]['convertParams'] = json_decode($file['convertParams']) ?: array();

            $useNum = $this->getCourseService()->searchLessonCount(array('mediaId' => $file['id']));

            $manageFilesUseNum = $this->getMaterialService()->getMaterialCountByFileId($file['id']);

            if ($files[$key]['targetType'] == 'coursematerial') {
                $files[$key]['useNum'] = $manageFilesUseNum;
            } else {
                $files[$key]['useNum'] = $useNum;
            }
        }

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($files, 'updatedUserId'));

        $storageSetting = $this->getSettingService()->get("storage");
        return $this->render('TopxiaWebBundle:CourseFileManage:index.html.twig', array(
            'type'           => $type,
            'course'         => $course,
            'courseLessons'  => $files,
            'users'          => ArrayToolkit::index($users, 'id'),
            'paginator'      => $paginator,
            'now'            => time(),
            'storageSetting' => $storageSetting
        ));
    }

    public function uploadAction(Request $request, $id)
    {
    }

    public function fileStatusAction(Request $request)
    {
        $currentUser = $this->getCurrentUser();

        if (!$currentUser->isTeacher() && !$currentUser->isAdmin()) {
            return $this->createJsonResponse(array());
        }

        $fileIds = $request->query->get('ids');

        if (empty($fileIds)) {
            return $this->createJsonResponse(array());
        }

        $fileIds = explode(',', $fileIds);

        $files = $this->getUploadFileService2()->findFiles($fileIds);

        return $this->createJsonResponse(FileFilter::filters($files));
    }

    public function showAction(Request $request, $id, $fileId)
    {
        $course = $this->getCourseService()->tryManageCourse($id);

        $file = $this->getUploadFileService()->getFile($fileId);

        if (empty($file)) {
            throw $this->createNotFoundException();
        }

        if ($id != $file["targetId"]) {
            throw $this->createNotFoundException();
        }

        $api    = CloudAPIFactory::create();
        $result = $api->get("/files/{$file['globalId']}/download");

        header("Location: {$result['url']}");
        exit();

        // if ($file['targetType'] == 'courselesson') {
        //     return $this->forward('TopxiaWebBundle:CourseLesson:file', array('fileId' => $file['id'], 'isDownload' => true));
        // } else if ($file['targetType'] == 'coursematerial' || $file['targetType'] == 'materiallib') {
        //     if ($file['storage'] == 'cloud') {
        //         $factory = new CloudClientFactory();
        //         $client = $factory->createClient();
        //         $client->download($client->getBucket(), $file['hashId'], 3600, $file['filename']);
        //     } else {
        //         return $this->createPrivateFileDownloadResponse($request, $file);
        //     }
        // }

        // throw $this->createNotFoundException();
    }

    public function convertAction(Request $request, $id, $fileId)
    {
        if ($id != 0) {
            $course = $this->getCourseService()->tryManageCourse($id);
        }

        $file = $this->getUploadFileService()->getFile($fileId);

        if (empty($file)) {
            throw $this->createNotFoundException();
        }

        $convertHash = $this->getUploadFileService()->reconvertFile(
            $file['id'],
            $this->generateUrl('uploadfile_cloud_convert_callback2', array(), true)
        );

        if (empty($convertHash)) {
            return $this->createJsonResponse(array('status' => 'error', 'message' => '文件转换请求失败，请重试！'));
        }

        return $this->createJsonResponse(array('status' => 'ok'));
    }

    public function uploadCourseFilesAction(Request $request, $id, $targetType)
    {
        if (!empty($id)) {
            $course = $this->getCourseService()->tryManageCourse($id);
        } else {
            $course = null;
        }

        $storageSetting = $this->getSettingService()->get('storage', array());
        return $this->render('TopxiaWebBundle:CourseFileManage:modal-upload-course-files.html.twig', array(
            'course'         => $course,
            'storageSetting' => $storageSetting,
            'targetType'     => $targetType,
            'targetId'       => $id
        ));
    }

    public function deleteCourseFilesAction(Request $request, $id, $type)
    {
        if (!empty($id)) {
            $course = $this->getCourseService()->tryManageCourse($id);
        }

        $ids = $request->request->get('ids', array());

        $this->getUploadFileService2()->deleteFiles($ids);

        return $this->createJsonResponse(true);
    }

    protected function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }

    protected function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }

    protected function getUploadFileService2()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService2');
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    }

    protected function getMaterialService()
    {
        return $this->getServiceKernel()->createService('Course.MaterialService');
    }

    protected function createPrivateFileDownloadResponse(Request $request, $file)
    {
        $response = BinaryFileResponse::create($file['fullpath'], 200, array(), false);
        $response->trustXSendfileTypeHeader();

        $file['filename'] = urlencode($file['filename']);
        $file['filename'] = str_replace('+', '%20', $file['filename']);

        if (preg_match("/MSIE/i", $request->headers->get('User-Agent'))) {
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$file['filename'].'"');
        } else {
            $response->headers->set('Content-Disposition', "attachment; filename*=UTF-8''".$file['filename']);
        }

        $mimeType = FileToolkit::getMimeTypeByExtension($file['ext']);

        if ($mimeType) {
            $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }
}

class FileFilter
{
    public static function filters($files)
    {
        $filterResult = array();

        if (empty($files)) {
            return $filterResult;
        }

        foreach ($files as $index => $file) {
            array_push($filterResult, array('id' => $file['id'], 'convertStatus' => $file['convertStatus']));
        }

        return $filterResult;
    }
}
