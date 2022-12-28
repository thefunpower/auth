<?php 
/*
    Copyright (c) 2021-2031, All rights reserved.
    This is NOT a freeware, use is subject to license terms 
    Connect Email: sunkangchina@163.com 
    Code Vesion: v1.0.x
*/
/**
 * 主要服务于后台管理员 
 */
if(!defined('VERSION')){die();} 
/**
 * 是否是超及管理员，就是用户ID为1的
 */ 
function is_admin(){
    $user_id = logined_user_id();  
    if($user_id == 1){
        return true;
    }
    return false;
} 

/**
 * 是否是登录会员
 */ 
function is_logined(){
    return logined_user_id()?true:false;
} 
/**
 * 获取登录者id,包含平台COOKIE以及小程序token登录的
 */
function get_admin_id()
{
    $admin_id = cookie(ADMIN_COOKIE_NAME) ?: 0;
    $api = api(false);
    $user_id = $api['user_id'];
    if($admin_id <= 0){
        $user = get_user($user_id);
        if($user['is_mp_admin'] == 1){
            $admin_id = $user_id;
        }
    }
    return $admin_id;
} 
/**
* 判断是否是超管，不是显示错误
*/
function is_admin_with_error(){
    if(!cookie(ADMIN_COOKIE_NAME)){
        echo lang('Access Deny');exit;
    }
}
/**
 * 接口判断是否是管理员登录
 */
function api_is_admin()
{
    if(!cookie(ADMIN_COOKIE_NAME)){
        json_error(['msg'=>lang('Access Deny')]);
    }
} 
/**
* 判断后台是否登录
*/
function check_admin_login($url = '')
{
    if(!cookie(ADMIN_COOKIE_NAME)){ 
        $jump = "/".ADMIN_DIR_NAME."/login.php"; 
        if($url){
            $jump = $jump."?url=".urlencode($url);
        }
        if($url === true){
            return false;
        }else{
            jump($jump);
        }
        
    }else{
        return true;
    }
}

/**
 * 已登录用户ID
 */ 
function logined_user_id(){
    $api = api(false);
    return cookie(ADMIN_COOKIE_NAME)?:$api['user_id'];
} 
/**
* 是否有权限 
*/ 
function has_access($key){
    return access($key);
} 

/**
 * 是否有权限，无权限抛出异常
 */
function access($name,$ret = false){
    if(!is_logined()){
        jump(ADMIN_DIR_NAME.'/login.php');
    }
    if(is_admin()){
        return true;
    }
    $acl = get_user_acl(); 
    if(in_array($name,$acl)){
        return true;
    }
    if($ret){
        return false;
    }
    json(['code'=>403,'msg'=>lang('Access Deny'),'type'=>'error']);
}
/**
 * 当前登录用户的ACL数组
 */
if(!function_exists('get_user_acl')){ 
    function get_user_acl(){
        $user_id  = logined_user_id();
        $res = db_get_one("user","*",['id'=>$user_id]);
        $acl = $res['acl']; 
        $acl_1 = [];
        if($acl){
            $acl_1 = $acl;
        } 
        $group_id = $res['group_id'];
        $acl = [];
        if($group_id){
            $acl      = db_get_one("user_group","acl",['id'=>$group_id]);            
        }
        $acl = array_merge($acl_1,$acl);
        return $acl?:[];
    }
}

/**
 * 是否有权限，无权限返回false
 */
function if_access($name){
    return access($name,true);
}
/**
* 判断有其中一个权限
*/
function if_access_or($str){  
    if(is_array($str)){
        $arr = $str;
    }else{
        $arr = string_to_array($str,['|',',']);
    }
    $flag = false; 
    foreach($arr as $v){ 
        if(if_access($v)){
            $flag = true;
        }
    }
    return $flag;
}
/**
 * 解析do_action
 */
function parse_action($li, $preg = 'do_action')
{
    $out = [];
    foreach ($li as $v) {
        $d = file_get_contents($v);
        if(!$d){
            continue;
        }
        preg_match_all('/\/\/(.*)\s*' . $preg . '(\(.*\))/', $d, $m);
        if ($m[1] && $m[2]) {
            foreach ($m[1] as $k => $desc) {
                $val = "";
                $val  = $m[2][$k];
                $val  = substr($val, 1, -1);
                $val  = str_replace("'", "", $val);
                if (strpos($val, ',') !== false) {
                    $arr = explode(',', $val);
                    $a   = $arr[0];
                    $b   = $arr[1];
                    $a = str_replace("\"", "", $a);
                    $array[] = [
                        'name'   => trim($desc),
                        'action' => trim($a),
                        'pars'   => $b,
                    ];
                } else {
                    $val = str_replace("\"", "", $val);
                    $array[] = [
                        'name'   => trim($desc),
                        'action' => trim($val),
                    ];
                } 
            }
        }
    }
    return $array;
}

