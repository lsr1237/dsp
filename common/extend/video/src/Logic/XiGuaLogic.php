<?php
declare (strict_types=1);

namespace common\extend\video\src\Logic;

use common\extend\video\src\Exception\ErrorVideoException;
use common\extend\video\src\Utils\CommonUtil;

/**
 * Created By 1
 * Author：smalls
 * Email：smalls0098@gmail.com
 * Date：2020/6/10 - 14:00
 **/
    class XiGuaLogic extends TouTiaoLogic
{

    public function setItemId()
    {
        preg_match('/group\/([0-9]+)\/?/i', $this->url, $match);
        if (CommonUtil::checkEmptyMatch($match)) {
            preg_match('/\/([0-9]+)\/?/i', $this->url, $match);
            if (CommonUtil::checkEmptyMatch($match)) {
                throw new ErrorVideoException("item_id获取失败");
            }
        }
        $this->itemId = $match[1];
    }
}