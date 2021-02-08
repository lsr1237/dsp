<?php
declare (strict_types=1);

namespace common\extend\video\src;

use common\extend\video\src\Exception\InvalidManagerException;
use common\extend\video\src\Interfaces\IVideo;
use common\extend\video\src\Tools\Bili;
use common\extend\video\src\Tools\DouYin;
use common\extend\video\src\Tools\HuoShan;
use common\extend\video\src\Tools\KuaiShou;
use common\extend\video\src\Tools\LiVideo;
use common\extend\video\src\Tools\MeiPai;
use common\extend\video\src\Tools\MiaoPai;
use common\extend\video\src\Tools\MoMo;
use common\extend\video\src\Tools\PiPiGaoXiao;
use common\extend\video\src\Tools\PiPiXia;
use common\extend\video\src\Tools\QQVideo;
use common\extend\video\src\Tools\QuanMingGaoXiao;
use common\extend\video\src\Tools\ShuaBao;
use common\extend\video\src\Tools\TaoBao;
use common\extend\video\src\Tools\TouTiao;
use common\extend\video\src\Tools\WeiBo;
use common\extend\video\src\Tools\WeiShi;
use common\extend\video\src\Tools\XiaoKaXiu;
use common\extend\video\src\Tools\XiGua;
use common\extend\video\src\Tools\ZuiYou;

/**
 * Created By 1
 * Author：smalls
 * Email：smalls0098@gmail.com
 * Date：2020/4/26 - 21:51
 **/

/**
 * @method static HuoShan HuoShan(...$params)
 * @method static DouYin DouYin(...$params)
 * @method static KuaiShou KuaiShou(...$params)
 * @method static TouTiao TouTiao(...$params)
 * @method static XiGua XiGua(...$params)
 * @method static WeiShi WeiShi(...$params)
 * @method static PiPiXia PiPiXia(...$params)
 * @method static ZuiYou ZuiYou(...$params)
 * @method static MeiPai MeiPai(...$params)
 * @method static LiVideo LiVideo(...$params)
 * @method static QuanMingGaoXiao QuanMingGaoXiao(...$params)
 * @method static PiPiGaoXiao PiPiGaoXiao(...$params)
 * @method static MoMo MoMo(...$params)
 * @method static ShuaBao ShuaBao(...$params)
 * @method static XiaoKaXiu XiaoKaXiu(...$params)
 * @method static Bili Bili(...$params)
 * @method static WeiBo WeiBo(...$params)
 * @method static MiaoPai MiaoPai(...$params)
 * @method static QQVideo QQVideo(...$params)
 * @method static TaoBao TaoBao(...$params)
 */
class VideoManager
{

    public function __construct()
    {
    }

    /**
     * @param $method
     * @param $params
     * @return mixed
     */
    public static function __callStatic($method, $params)
    {
        $app = new self();
        return $app->create($method, $params);
    }

    /**
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws InvalidManagerException
     */
    private function create(string $method, array $params)
    {
        $className = __NAMESPACE__ . '\\Tools\\' . $method;
        if (!class_exists($className)) {
            throw new InvalidManagerException("the method name does not exist . method : {$method}");
        }
        return $this->make($className, $params);
    }

    /**
     * @param string $className
     * @param array $params
     * @return mixed
     * @throws InvalidManagerException
     */
    private function make(string $className, array $params)
    {
        $app = new $className($params);
        if ($app instanceof IVideo) {
            return $app;
        }
        throw new InvalidManagerException("this method does not integrate IVideo . namespace : {$className}");
    }
}