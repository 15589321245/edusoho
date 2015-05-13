<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Topxia\Service\Common\ServiceKernel;

class PartnerDiscuzController extends BaseController
{

    public function notifyAction(Request $request)
    {

        $this->initUcenter();

        $_DCACHE = $get = $post = array();
        $code = @$_GET['code'];
        parse_str(uc_authcode($code, 'DECODE', UC_KEY), $get);
        if(MAGIC_QUOTES_GPC) {
            $get = $this->stripslashes($get);
        }

        $timestamp = time();
        if($timestamp - $get['time'] > 3600) {
            return new Response('Authracation has expiried');
        }
        if(empty($get)) {
            return new Response('Invalid Request');
        }
        $action = $get['action'];

        $this->requireClientFile('lib/xml.class.php');

        $xml = file_get_contents('php://input');
        $post = xml_unserialize($xml);

        if (!in_array($get['action'], array('test', 'deleteuser', 'renameuser', 'gettag', 'synlogin', 'synlogout', 'updatepw', 'updatebadwords', 'updatehosts', 'updateapps', 'updateclient', 'updatecredit', 'getcreditsettings', 'updatecreditsettings'))) {
            return new Response(API_RETURN_FAILED);
        }

        $method = 'do' . ucfirst($get['action']);
        $result = $this->$method($request, $get, $post);
        return new Response($result);
    }

    private function initUcenter()
    {
        define('UC_CLIENT_VERSION', '1.6.0');
        define('UC_CLIENT_RELEASE', '20110501');

        define('API_DELETEUSER', 1);
        define('API_RENAMEUSER', 1);
        define('API_GETTAG', 1);
        define('API_SYNLOGIN', 1);
        define('API_SYNLOGOUT', 1);
        define('API_UPDATEPW', 1);
        define('API_UPDATEBADWORDS', 1);
        define('API_UPDATEHOSTS', 1);
        define('API_UPDATEAPPS', 1);
        define('API_UPDATECLIENT', 1);
        define('API_UPDATECREDIT', 1);
        define('API_GETCREDIT', 1);
        define('API_GETCREDITSETTINGS', 1);
        define('API_UPDATECREDITSETTINGS', 1);
        define('API_ADDFEED', 1);
        define('API_RETURN_SUCCEED', '1');
        define('API_RETURN_FAILED', '-1');
        define('API_RETURN_FORBIDDEN', '-2');

        set_magic_quotes_runtime(0);

        defined('MAGIC_QUOTES_GPC') || define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
        require_once realpath($this->container->getParameter('kernel.root_dir')) . '/config/uc_client_config.php';
        $this->requireClientFile('client.php');
    }

    private function requireClientFile($path)
    {
        $clientDirectory = realpath($this->container->getParameter('kernel.root_dir') . '/../vendor_user/uc_client');
        require_once $clientDirectory . '/' . $path;
    }

    private function writeCacheFile($filename, $content)
    {
        $cacheDirectory = $this->container->getParameter('kernel.root_dir') . '/data/discuz/';

        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory);
        }

        $cachefile = $cacheDirectory . $filename;
        $fp = fopen($cachefile, 'w');
        fwrite($fp, $content);
        fclose($fp);
    }

    private function stripslashes($string) {
        if(is_array($string)) {
            foreach($string as $key => $val) {
                $string[$key] = $this->stripslashes($val);
            }
        } else {
            $string = stripslashes($string);
        }
        return $string;
    }

}