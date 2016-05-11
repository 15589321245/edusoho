<?php
namespace Permission\PermissionBundle\TwigExtension;

use Permission\Common\PermissionBuilder;

class PermissionExtension extends \Twig_Extension
{
    protected $container;

    protected $builder = null;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('parent_permission', array($this, 'getParentPermission'))
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('permission', array($this, 'getPermissionByCode')),
            new \Twig_SimpleFunction('sub_permissions', array($this, 'getSubPermissions')),
            new \Twig_SimpleFunction('permission_path', array($this, 'getPermissionPath'), array('needs_context' => true, 'needs_environment' => true)),
            new \Twig_SimpleFunction('grouped_permissions', array($this, 'groupedPermissions')),
            new \Twig_SimpleFunction('has_permission', array($this, 'hasPermission')),
            new \Twig_SimpleFunction('eval_expression', array($this, 'evalExpression'), array('needs_context' => true, 'needs_environment' => true))
        );
    }

    public function getPermissionPath($env, $context, $menu)
    {
        $menus = $this->getSubPermissions($menu['code']);

        if ($menus) {
            $menu  = current($menus);
            $menus = $this->getSubPermissions($menu['code']);

            if ($menus) {
                $menu = current($menus);
            }
        }

        $route  = empty($menu['router_name']) ? $menu['code'] : $menu['router_name'];
        $params = empty($menu['router_params']) ? array() : $menu['router_params'];

        foreach ($params as $key => $value) {
            if(strpos($value, "(") === 0) {
                $value = $this->evalExpression($env, $context['_context'], $value);
                $params[$key] = $value;
            } else {
                $params[$key] = "{$value}";
            }

        }
        
        return $this->container->get('router')->generate($route, $params);
    }

    public function evalExpression($twig, $context, $code)
    {
        $code = trim($code);
        if(strpos($code, "(") === 0) {
            $code = substr($code, 1, strlen($code)-2);
        } else {
            $code = "'{$code}'";
        }

        $loader = new \Twig_Loader_Array(array(
            'expression.twig' => '{{'.$code.'}}',
        ));

        $twig = new \Twig_Environment($loader);

        return $twig->render('expression.twig', $context);
    }

    public function getPermissionByCode($code)
    {
        return $this->createPermissionBuilder()->getMenuByCode($code);
    }

    public function hasPermission($code)
    {
        $permission = $this->createPermissionBuilder()->getMenuByCode($code);
        return !empty($permission);
    }

    public function getSubPermissions($code, $group = '1')
    {
        return $this->createPermissionBuilder()->getMenuChildren($code, $group);
    }

    public function groupedPermissions($code)
    {
        return $this->createPermissionBuilder()->groupedMenus($code);
    }

    public function getParentPermission($code)
    {
        return $this->createPermissionBuilder()->getParentMenu($code);
    }

    private function createPermissionBuilder()
    {
        if (empty($this->builder)) {
            $this->builder = new PermissionBuilder();
        }

        return $this->builder;
    }

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    public function getName()
    {
        return 'topxia_permission_twig';
    }
}
