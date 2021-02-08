<?php

namespace common\extend\video\Tests\Tools;

use PHPUnit\Framework\TestCase;
use common\extend\video\Enumerates\BiliQualityType;
use common\extend\video\VideoManager;

class BiliTest extends TestCase
{

    public function testStart()
    {
        $res = VideoManager::Bili()->setUrl("https://b23.tv/av84665662")->setQuality(BiliQualityType::LEVEL_2)->execution();
        var_dump($res);
    }
}
