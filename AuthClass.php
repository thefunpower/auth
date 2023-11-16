<?php
class AuthClass
{
    /**
     * 加载权限列表
     */
    public function load($opt = ['plugins','modules'])
    {
        $arr = [];
        foreach($opt as $v){
            $arr = $arr+(self::_load($v)?:[]);
        }  
        return $arr;
    } 
    public static function _load($dir_name = 'plugins')
    {
        $li = [];
        $dir = PATH . '/'.$dir_name.'/';
        $actived = has_actived_plugin();
        $dirs[] = PATH . '/app/admin/';
        foreach ($actived as $name => $v) {
            $dirs[] = $dir . $name . '/api';
        }

        foreach ($dirs as $v) {
            $find = get_deep_dir($v);
            $li   = array_merge($find, $li);
        }
        $list = [];
        foreach ($li as $key => $v) {
            if (get_ext($v) == 'php') {
                $list[] = $v;
            }
        }
        $arr = parse_action($list, 'access');
        $lists = [];
        foreach ($arr as $v) {
            $name = $v['name'];
            $k = substr($name, 0, strrpos($name, '.'));
            $v['label'] = substr($name, strrpos($name, '.') + 1);
            $lists[$k][$v['label']] = $v;
        }
        //支持三层结构显示
        $new_list = [];
        foreach ($lists as $k => $v) {
            if (strpos($k, '.') !== false) {
                $arr = explode('.', $k);
                $k1 = $arr[0];
                $k2 = $arr[1];
                $new_list[$k1][$k2]  = $v;
            } else {
                foreach ($v as $kk => $vv) {
                    $new_list[$k][$kk] = $vv;
                }
            }
        }
        krsort($new_list);
        $lists = [];
        foreach ($new_list as $k => $v) {
            $k = preg_replace('/^\d+/', '', $k);
            ksort($v);
            $new_v = [];
            foreach ($v as $k1 => $v1) {
                $k1 = preg_replace('/^\d+/', '', $k1);
                $new_v[$k1] = $v1;
            }
            $lists[$k] = $new_v;
        }
        return $lists;
    }
}
