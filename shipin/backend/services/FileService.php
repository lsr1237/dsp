<?php
namespace backend\services;

use Yii;
use yii\helpers\Json;
use backend\bases\BackendService;

class FileService extends BackendService
{
    public static function saveParams($data = [], $file, $reset = false, $remove = false)
    {
        if (!file_exists($file)) {
           return false;
        }

        $old = include($file);
        if (is_array($old)) {
            foreach ($old as $key => $value) {
                if (!isset($data[$key])) {
                    if (!$remove) {
                        $data[$key] = $value;
                    }
                } else {
                    if (!$reset) {
                        $data[$key] = $value;
                    }
                }
            }
        }
        
        //写入文件
        $str = "<?php\nreturn [\n";
        foreach ($data as $key => $value) {
            $value = htmlspecialchars($value);
            $str .= "\t'{$key}' => '{$value}',\n";
        }
        $str .= '];';
        if(!file_put_contents($file,$str))
            return false;
        return true;
    }

    /**
     * 修改配置文件（存在多维数组，最多三维，第二维为非关联数组）
     * @param array $data 做修改的阐述
     * @param $file 文件地址
     * @param bool $reset
     * @param bool $remove
     * @return bool 文件保存成功返回true 错误返回false
     */
    public static function saveParamsArr($data = [], $file, $reset = false, $remove = false)
    {
        if (!file_exists($file)) {
            return false;
        }

        $old = include($file);
        if (is_array($old)) {
            foreach ($old as $key => $value) {
                if (!isset($data[$key])) {
                    if (!$remove) {
                        $data[$key] = $value;
                    }
                } else {
                    if (!$reset) {
                        $data[$key] = $value;
                    }
                }
            }
        }

        // 写入文件
        $str = "<?php\nreturn [\n";
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $str .= "\t'{$key}' =>[\n";
                foreach ($value as $k => $v) {
                    if (is_array($v)) {
                        $str .= "\t\t[\n";
                        foreach ($v as $ke => $va) {
                            $va = htmlspecialchars($va);
                            $str .= "\t\t\t'{$ke}' => '{$va}',\n";
                        }
                        $str .= "\t\t],\n";
                    } else {
                        $v = htmlspecialchars($v);
                        $str .= "\t\t'{$k}' => '{$v}',\n";
                    }
                }
                $str .= "\t],\n";
            } else {
                $value = htmlspecialchars($value);
                $str .= "\t'{$key}' => '{$value}',\n";
            }
        }
        $str .= '];';
        if(!file_put_contents($file,$str))
            return false;
        return true;
    }
}

