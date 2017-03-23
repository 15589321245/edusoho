<?php

namespace Biz\Common\Mail;

use Topxia\Service\Common\ServiceKernel;

class MailFactory
{
    /**
     * @param $mailOptions
     *
     * @return Mail
     */
    public static function create($mailOptions)
    {
        $setting = ServiceKernel::instance()->createService('System:SettingService');

        $cloudConfig = $setting->get('cloud_email_crm', array());

        if (isset($cloudConfig['status']) && $cloudConfig['status'] == 'enable') {
            $mail = new CloudMail($mailOptions);
        } else {
            $mail = new NormalMail($mailOptions);
        }

        return $mail;
    }
}
