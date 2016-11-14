<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

class CategoryController extends BaseController
{
    public function allAction()
    {
        $categories = $this->getCategoryService()->findCategories(1);

        $data = array();
        foreach ($categories as $category) {
            $data[$category['id']] = array($category['name'], $category['parentId']);
        }

        return $this->createJsonResponse($data);
    }


    protected function makeCategories()
    {
        $group = $this->getCategoryService()->getGroupByCode('course');

        if (empty($group)) {
            $categories = array();
        } else {
            $categories = $this->getCategoryService()->getCategoryTree($group['id']);

            foreach ($categories as $id => $category) {
                if ($categories[$id]['parentId'] != '0') {
                    unset($categories[$id]);
                }
            }
        }

        return $categories;
    }

    protected function makeTags()
    {
        $tagGroups = $this->getTagService()->findTagGroups();

        foreach ($tagGroups as $key => $tagGroup) {
            $allTags = $this->getTagService()->findTagsByGroupId($tagGroup['id']);
            $tagGroups[$key]['subs'] = $allTags;
        }

        return $tagGroups;
    }

    protected function makeSubCategories($category)
    {
        $subCategories = array();

        $categoryArray = $this->getCategoryService()->getCategoryByCode($category['category']);

        if (!empty($categoryArray) && $categoryArray['parentId'] == 0) {
            $subCategories = $this->getCategoryService()->findAllCategoriesByParentId($categoryArray['id']);
        }

        if (!empty($categoryArray) && $categoryArray['parentId'] != 0) {
            $subCategories = $this->getCategoryService()->findAllCategoriesByParentId($categoryArray['parentId']);
        }

        return $subCategories;
    }

    public function treeNavAction(Request $request, $category, $tags, $path, $filter = array('price'=>'all','type'=>'all', 'currentLevelId'=>'all'), $orderBy = 'latest')
    {
        $categories = $this->makeCategories();

        $tagGroups = $this->makeTags();

        $subCategories = $this->makeSubCategories($category);

        return $this->render("TopxiaWebBundle:Category:explore-nav.html.twig", array(
            'selectedCategory'    => $category['category'],
            'selectedSubCategory' => $category['subCategory'],
            'categories'          => $categories,
            'subCategories'       => $subCategories,
            'path'                => $path,
            'filter'              => $filter,
            'orderBy'             => $orderBy,
            'tagGroups'           => $tagGroups,
            'tags'                => $tags,
            // 'groupId'             => $groupId,
            // 'groupIds'            => $groupIds
        ));
    }

    protected function getTagService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.TagService');
    }

    protected function getCategoryService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.CategoryService');
    }
}
