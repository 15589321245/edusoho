<?php

namespace AppBundle\Twig;

use Biz\System\Service\SettingService;
use Codeages\Biz\Framework\Context\Biz;
use Biz\Course\Service\CourseSetService;

class AppExtension extends \Twig_Extension
{
    /**
     * @var Biz
     */
    protected $biz;

    public function __construct($biz)
    {
        $this->biz = $biz;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('currency', array($this, 'currency')),
            new \Twig_SimpleFilter('json_encode_utf8', array($this, 'jsonEncodeUtf8')),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('services', array($this, 'buildServiceTags')),
            new \Twig_SimpleFunction('classroom_services', array($this, 'buildClassroomServiceTags')),
            new \Twig_SimpleFunction('count', array($this, 'count')),
            new \Twig_SimpleFunction('course_cover', array($this, 'courseCover')),
            new \Twig_SimpleFunction('course_set_cover', array($this, 'courseSetCover')),
        );
    }

    /*
     * 返回金额的货币表示
     * @param money 金额，单位：分
     *
     */
    public function currency($money)
    {
        //当前仅考虑中文的货币处理；
        if ($money == 0) {
            return '0';
        }

        return sprintf('%.2f', $money);
    }

    /**
     * json_encode($arr, JSON_UNESCAPED_UNICODE) 需要PHP5.4以上版本，所以自己写一个以便支持PHP5.3.
     *
     * @param  $arr
     *
     * @return string
     */
    public function jsonEncodeUtf8($arr)
    {
        if (empty($arr)) {
            return '[]';
        }

        $encoded = json_encode($arr);

        return preg_replace_callback('/\\\\u(\w{4})/', function ($matches) {
            return html_entity_decode('&#x'.$matches[1].';', ENT_COMPAT, 'UTF-8');
        }, $encoded);
    }

    public function buildServiceTags($selectedTags)
    {
        $tags = array(
            array(
                'code' => 'homeworkReview',
                'short_name' => '练',
                'full_name' => '24小时作业批阅',
                'summary' => '24小时内完成作业批阅，即时反馈并巩固您的学习效果',
                'active' => 0,
            ),
            array(
                'code' => 'testpaperReview',
                'short_name' => '试',
                'full_name' => '24小时阅卷点评',
                'summary' => '24小时内批阅您提交的试卷，给予有针对性的点评',
                'active' => 0,
            ),
            array(
                'code' => 'teacherAnswer',
                'short_name' => '问',
                'full_name' => '提问必答',
                'summary' => '对于提问做到有问必答，帮您扫清学习过程中的种种障碍',
                'active' => 0,
            ),
            array(
                'code' => 'liveAnswer',
                'short_name' => '疑',
                'full_name' => '一对一在线答疑',
                'summary' => '提供专属的一对一在线答疑，快速答疑解惑。',
                'active' => 0,
            ),
        );

        if (empty($selectedTags)) {
            return $tags;
        }
        foreach ($tags as &$tag) {
            if (in_array($tag['full_name'], $selectedTags)) {
                $tag['active'] = 1;
            }
        }

        return $this->sortTags($tags);
    }

    public function buildClassroomServiceTags($selectedTags)
    {
        $tags = array(
            array(
                'code' => 'homeworkReview',
                'short_name' => '练',
                'full_name' => '24小时作业批阅',
                'summary' => '24小时内完成作业批阅，即时反馈并巩固您的学习效果',
                'active' => 0,
            ),
            array(
                'code' => 'testpaperReview',
                'short_name' => '试',
                'full_name' => '24小时阅卷点评',
                'summary' => '24小时内批阅您提交的试卷，给予有针对性的点评',
                'active' => 0,
            ),
            array(
                'code' => 'teacherAnswer',
                'short_name' => '问',
                'full_name' => '提问必答',
                'summary' => '对于提问做到有问必答，帮您扫清学习过程中的种种障碍',
                'active' => 0,
            ),
            array(
                'code' => 'liveAnswer',
                'short_name' => '疑',
                'full_name' => '一对一在线答疑',
                'summary' => '提供专属的一对一在线答疑，快速答疑解惑。',
                'active' => 0,
            ),
            array(
                'code' => 'event',
                'short_name' => '动',
                'full_name' => '班级活动',
                'summary' => '不定期组织各种线上或线下的班级活动，让学习更加生动有趣，同学关系更为紧密',
                'active' => 0,
            ),
            array(
                'code' => 'workAdvise',
                'short_name' => '业',
                'full_name' => '就业指导',
                'summary' => '完成全部学习后，老师对您的学习成果和能力水平给出评估，并提供专业化的就业指导',
                'active' => 0,
            ),
        );

        if (empty($selectedTags)) {
            return $tags;
        }
        foreach ($tags as &$tag) {
            //为了兼容course和classroom的数据保存格式
            if (in_array($tag['code'], $selectedTags)) {
                $tag['active'] = 1;
            }
        }

        return $this->sortTags($tags);
    }

    public function courseCover($course, $type = 'middle')
    {
        $courseSet = null;
        if (!empty($course)) {
            if (!empty($course['courseSet'])) {
                $courseSet = $course['courseSet'];
            } else {
                $courseSet = $this->getCourseSetService()->getCourseSet($course['courseSetId']);
            }
        }

        return $this->courseSetCover($courseSet, $type);
    }

    public function courseSetCover($courseSet, $type = 'middle')
    {
        $coverPath = null;
        if (!empty($courseSet)) {
            $cover = $courseSet['cover'];
            if (!empty($cover) && !empty($cover[$type])) {
                $coverPath = $cover[$type];
            }
        }

        if (empty($coverPath)) {
            $settings = $this->getSettingService()->get('default');
            $coverPath = !empty($settings['course.png']) ? $settings['course.png'] : null;
        }

        return $coverPath;
    }

    protected function sortTags($tags)
    {
        if (empty($tags)) {
            return $tags;
        }
        usort($tags, function ($t1, $t2) {
            return $t2['active'] - $t1['active'];
        });

        return $tags;
    }

    public function count($arr)
    {
        if (empty($arr)) {
            return 0;
        }

        return count($arr);
    }

    public function getName()
    {
        return 'app_twig';
    }

    /**
     * @return CourseSetService
     */
    protected function getCourseSetService()
    {
        return $this->biz->service('Course:CourseSetService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->biz->service('System:SettingService');
    }
}
