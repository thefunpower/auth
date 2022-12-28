<?php

/*
    Copyright (c) 2021-2031, All rights reserved.
    This is NOT a freeware, use is subject to license terms 
    Connect Email: sunkangchina@163.com 
    Code Vesion: v1.0.x
*/

if (!defined('VERSION')) {
    die();
}
/**
 * 所有会员
 */
function get_user_all($where = [])
{
    $all  = db_get("user", "*", $where);
    $list = [];
    foreach ($all as $v) {
        $list[] = get_user($v['id']);
    }
    return $list;
}
/**
 * 根据用户id查用户信息
 */
function get_user($user_id,$fields=[])
{
    static $_user;
    if($_user[$user_id]){
        return $_user[$user_id];
    }
    $where['id'] = $user_id;
    $user = get_user_where($where);
    if($fields){
        foreach($fields as $k){
            $new_user[$k] = $user[$k];
        }
        $user = $new_user;
    }
    do_action('get_user', $user);
    $_user[$user_id] = $user; 
    return $user;
}
/**
 * 查用户信息，where条件
 */
function get_user_where($where = [])
{
    $user    = db_get_one('user', '*', $where);
    if (!$user) {
        return;
    }
    $user_id = $user['id'];
    $login_where = ['user_id' => $user_id];
    $from = g('from');
    if ($from) {
        $login_where['type'] = $from;
    }
    $login   = db_get_one('login', '*', $login_where) ?: [];
    $user    = array_merge($login, $user);
    //meta字段 
    $all = get_user_meta($user_id);
    foreach ($all as $k => $v) {
        $user[$k] = $v;
    }
    $user['group_name'] = user_group_get($user['group_id'])['name'];
    if ($login['avatar_url']) {
        //  $user['avatar_url'] = $login['avatar_url'];
    }
    return $user;
}
/**
 * 取用户扩展字段值
 */
function get_user_meta_where($where = [], $return_row = false)
{
    $user_id = $where['user_id'];
    foreach ($where as $k => $v) {
        if ($k != 'user_id') {
            unset($where[$k]);
            $where['AND'] = ['title' => $k, 'value[~]' => $v];
        }
    }
    if ($user_id) {
        $where['AND'] = ['user_id' => $user_id];
    }
    $new_where['AND'] = $where;
    $all  = db_select('user_meta', '*', $new_where);
    $meta = [];
    foreach ($all as $v) {
        $val = $v['value']; 
        if ($return_row) {
            $meta[$v['title']] = $val;
        } else {
            $meta[] = $v;
        }
    }
    return $meta;
}

/**
 * 取用户扩展字段值
 */
function get_user_meta($user_id)
{
    return get_user_meta_where(['user_id' => $user_id], true);
}
/**
 * 更新用户的meta信息
 * @param array $meta ['nickname'=>'']
 */
function set_user_meta($user_id, $meta)
{
    $user  = db_get_one('user', '*', ['id' => $user_id]);
    if ($user) {
        $all = db_select('user_meta', '*', ['user_id' => $user_id]);
        $insert  = $update = [];
        foreach ($meta as $k => $v) {
            $one = db_get_one('user_meta', '*', ['user_id' => $user_id, 'title' => $k]);
            $id = $one['id'];
            if (is_array($v)) {
                //无需要json_encode，在db中已处理 
            }
            if ($id) {
                db_update('user_meta', ['title' => $k, 'value' => $v], ['id' => $id]);
            } else {
                db_insert('user_meta', ['title' => $k, 'value' => $v, 'user_id' => $user_id]);
            }
        }
    }
}
/**
* 更新用户信息
*/
function set_user($user_id,$data = []){
    db_update("user",$data,['id'=>$user_id]);
    return $user_id;
}
//部门tree
function user_group_tree($id = null)
{
    $where = [
        'status' => 1,
        'ORDER' => [
            'sort' => 'DESC'
        ]
    ];
    $title = get_post('name');
    if ($title) {
        $where['name[~]'] = $title;
    }
    $where['ORDER'] = ['sort' => 'DESC', 'pid' => "ASC"];
    $all = db_get("user_group", "*", $where);
    foreach ($all as $v) {
        $v['label'] = $v['name'];
        $v['_pid_name'] = user_group_get($v['pid'])['name'];
        $list[] = $v;
    }
    $list =  array_to_tree(
        $list,
        'id',
        $pid = 'pid',
        $child = 'children',
        $root = 0,
        $id
    );
    $list =  array_values($list);
    return $list;
}

/**
 * 取单个用户组信息
 */
function user_group_get($group_id)
{
    static $obj;
    if ($obj[$group_id]) {
        return $obj[$group_id];
    }
    $one = db_get_one("user_group", "*", ['id' => $group_id]);
    $one['_pid_name'] = db_get_one("user_group", "*", ['id' => $one['pid']]);
    ['name']; 
    $obj[$group_id] = $one;
    return $one;
}

/**
 * 创建或更新用户
 */
function admin_user($user, $pwd, $tag)
{
    $find = db_get_one('user', '*', ['user' => $user]);
    if (!$find) {
        if ($user && $pwd) {
            $id = db_insert('user', [
                'user'  => $user,
                'pwd'   => md5($pwd),
                'tag'   => $tag,
                'created_at' => now()
            ]);
        }
    } else {
        $id = $find['id'];
        if ($pwd) {
            db_update('user', ['pwd' => md5($pwd)], ['id' => $id]);
        }
    }
    return $id;
}
include __DIR__.'/AuthClass.php';