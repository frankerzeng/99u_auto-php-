<?php
require_once "im_uc.php";
$user_id = '741007';

// 送花列表
$conn = mysql_connect('localhost', 'root', 'root') or die("error connecting");
mysql_select_db('im');
$sql = "select * from send WHERE status =1";
$result = mysql_query($sql, $conn);
$return = [];
while ($row = mysql_fetch_assoc($result)) {
    $return[] = $row;
}

//登陆
im_uc::$_mac_token = im_uc::token($user_id, "密码");

// 签到
$ret = im_uc::sign_in($user_id);
print_r('签到----');
print_r($ret);

// 日清
$ret = im_uc::sign_out_new($user_id);
print_r('日清----');

print_r($ret);

// 生日祝福
$users = im_uc::birthday_users();
print_r('生日祝福----');
print_r($users);

foreach ($users['items'] as $user) {
    im_uc::bless($user['user_id']);
}

foreach ($return as $user) {
    im_uc::send_flower($user['user_id']);
}

// 领积分
$list = im_uc::get_receive_point_list($user_id);
print_r('生日祝福----');
print_r($list);

foreach ($list as $value) {
    if (!$value['bAdd']) {
        im_uc::receive_point($user_id, $value['AutoCode']);
    }
}

echo "{$user_id} finished~<br />";

