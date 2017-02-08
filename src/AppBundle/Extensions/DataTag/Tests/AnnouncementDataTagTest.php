<?php

namespace AppBundle\Extensions\DataTag\Test;

use Biz\Announcement\Service\AnnouncementService;
use Biz\BaseTestCase;;
use AppBundle\Extensions\DataTag\AnnouncementDataTag;

class AnnouncementDataTagTest extends BaseTestCase
{

    public function testGetData()
    {
        $announcement1 = $this->getAnnouncementService()->createAnnouncement(array(
        "title"=>"Announcement1 ",
        "url"=>"http://",
        "userId"=>"1",
        "startTime" => "2015-05-10 15:40",
        "endTime" => "2015-05-11 15:35"
        ));

        $announcement2 = $this->getAnnouncementService()->createAnnouncement(array(
        "title"=>"Announcement2 ",
        "url"=>"http://",
        "userId"=>"1",
        "startTime" =>"2015-05-10 15:40",
        "endTime" => "2015-05-11 15:35"
        ));

        $announcement3 = $this->getAnnouncementService()->createAnnouncement(array(
        "title"=>"Announcement3 ",
        "url"=>"http://",
        "userId"=>"1",
        "startTime" =>"2015-05-10 15:40",
        "endTime" => "2015-05-11 15:35"
        ));

        $announcement4 = $this->getAnnouncementService()->createAnnouncement(array(
        "title"=>"Announcement4 ",
        "url"=>"http://",
        "userId"=>"1",
        "startTime" =>"2015-05-10 15:40",
        "endTime" => "2015-05-11 15:35"
        ));

        $announcement5 = $this->getAnnouncementService()->createAnnouncement(array(
        "title"=>"Announcement5 ",
        "url"=>"http://",
        "userId"=>"1",
        "startTime" =>"2015-05-10 15:40",
        "endTime" => "2015-05-11 15:35"
        ));

        $dataTag = new AnnouncementDataTag();
        $announcement = $dataTag->getData(array('count' => "5"));
        $this->assertEquals(5, count($announcement));
    }

    /**
     * @return AnnouncementService
     */
    private function getAnnouncementService()
    {
        return $this->getServiceKernel()->createService('Announcement:AnnouncementService');
    }
}
