<?php
namespace Permission\PermissionBundle\Controller;

use Permission\Common\PermissionBuilder;
use Topxia\Common\Paginator;
use Topxia\Common\ArrayToolkit;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Topxia\AdminBundle\Controller\BaseController;

class RoleController extends BaseController
{
    public function indexAction(Request $request)
    {
        $fields = $request->query->all();
        $fields = ArrayToolkit::filter($fields, array(
            'keyword'     => '',
            'keywordType' => ''
        ));
        $conditons = array();

        if (isset($fields['keywordType']) && !empty($fields['keywordType'])) {
            $conditons[$fields['keywordType']] = $fields['keyword'];
        }

        $paginator = new Paginator(
            $this->get('request'),
            $this->getRoleService()->searchRolesCount($conditons),
            30
        );

        $roles = $this->getRoleService()->searchRoles(
            $conditons,
            'created',
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('PermissionBundle:Role:index.html.twig', array(
            'roles'     => $roles,
            'paginator' => $paginator
        ));
    }

    public function createAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {
            $params         = $request->request->all();
            $params['data'] = json_decode($params['data'], true);
            $role           = $this->getRoleService()->createRole($params);
            return $this->createJsonResponse(true);
        }

        $permissionBuilder = new PermissionBuilder();
        $res = $permissionBuilder->getOriginPermissionTree();
        return $this->render('PermissionBundle:Role:role-modal.html.twig', array('menus' => json_encode($res), 'model' => 'create'));
    }

    public function editAction(Request $request, $id)
    {
        $role = $this->getRoleService()->getRole($id);
        $permissionBuilder = new PermissionBuilder();
        $res = $permissionBuilder->getOriginPermissionTree();

        if ('POST' == $request->getMethod()) {
            $params         = $request->request->all();
            $params['data'] = json_decode($params['data'], true);
            $role           = $this->getRoleService()->updateRole($id, $params);
            return $this->createJsonResponse(true);
        }

        if(!empty($role['data'])){
            foreach ($res as $key => &$permission) {
                if(in_array($permission['code'], $role['data'])) {
                    $permission['checked'] = true;
                }
            }
        }

        return $this->render('PermissionBundle:Role:role-modal.html.twig', array('menus' => json_encode($res), 'model' => 'edit', 'role' => $role));
    }

    public function showAction(Request $request, $id)
    {
        $role = $this->getRoleService()->getRole($id);
        $permissionBuilder = new PermissionBuilder();

        $res  = $permissionBuilder->getOriginMenus();

        foreach ($res as &$re) {
            if (in_array($re['code'], $role['data'])) {
                $re['checked'] = 'true';
            }

            $re['chkDisabled'] = 'true';
        }

        return $this->render('PermissionBundle:Role:role-modal.html.twig', array('menus' => json_encode($res), 'model' => 'show', 'role' => $role));
    }

    protected function dataPrepare($position)
    {
        $configPaths = array();
        $rootDir     = realpath(__DIR__.'/../../../../');

        $configPaths[] = "{$rootDir}/src/Topxia/WebBundle/Resources/config/menus_{$position}.yml";
        $configPaths[] = "{$rootDir}/src/Topxia/AdminBundle/Resources/config/menus_{$position}.yml";

        $configPaths[] = "{$rootDir}/src/Classroom/ClassroomBundle/Resources/config/menus_{$position}.yml";

        $count = $this->getAppService()->findAppCount();
        $apps  = $this->getAppService()->findApps(0, $count);

        foreach ($apps as $app) {
            if ($app['type'] != 'plugin') {
                continue;
            }

            $code          = ucfirst($app['code']);
            $configPaths[] = "{$rootDir}/plugins/{$code}/{$code}Bundle/Resources/config/menus_{$position}.yml";
        }

        $configPaths[] = "{$rootDir}/src/Custom/WebBundle/Resources/config/menus_{$position}.yml";
        $configPaths[] = "{$rootDir}/src/Custom/AdminBundle/Resources/config/menus_{$position}.yml";

        $menus = array();

        foreach ($configPaths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            $menu = Yaml::parse($path);

            if (empty($menu)) {
                continue;
            }

            $menus = array_merge($menus, $menu);
        }

        $i = 0;

        foreach ($menus as $key => &$menu) {
            $menu['id'] = $i++;

            if (!empty($menu['parent'])) {
                $menu['pId'] = $menus[$menu['parent']]['id'];
            } else {
                $menu['pId'] = 0;
            }

            $menu['code'] = $key;
        }

        $menus = ArrayToolkit::index($menus, 'id');

        return $menus;
    }

    public function checkNameAction(Request $request)
    {
        $name    = $request->query->get('value');
        $exclude = $request->query->get('exclude');

        $avaliable = $this->getRoleService()->isRoleNameAvalieable($name, $exclude);

        if ($avaliable) {
            $response = array('success' => true, 'message' => '');
        } else {
            $response = array('success' => false, 'message' => '权限名称已存在');
        }

        return $this->createJsonResponse($response);
    }

    public function checkCodeAction(Request $request)
    {
        $code    = $request->query->get('value');
        $exclude = $request->query->get('exclude');

        $avaliable = $this->getRoleService()->isRoleCodeAvalieable($code, $exclude);

        if ($avaliable) {
            $response = array('success' => true, 'message' => '');
        } else {
            $response = array('success' => false, 'message' => 'code已存在');
        }

        return $this->createJsonResponse($response);
    }

    protected function getRoleService()
    {
        return $this->getServiceKernel()->createService('Permission:Role.RoleService');
    }

    protected function getAppService()
    {
        return $this->getServiceKernel()->createService('CloudPlatform.AppService');
    }

}
