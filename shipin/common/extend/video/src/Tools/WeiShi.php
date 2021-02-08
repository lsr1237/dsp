<?php
declare (strict_types=1);

namespace common\extend\video\src\Tools;
/**
 * Created By 1
 * Author：smalls
 * Email：smalls0098@gmail.com
 * Date：2020/4/26 - 21:57
 **/

use common\extend\video\src\Interfaces\IVideo;

class WeiShi extends Base implements IVideo
{

    /**
     * 更新时间：2020/7/31
     * @param string $url
     * @return array
     */
    public function start(string $url): array
    {
        $this->make();
        $this->logic->setOriginalUrl($url);
        $this->logic->checkUrlHasTrue();
        $this->logic->setFeedId();
        $this->logic->setContents();
        return $this->exportData();
    }
}