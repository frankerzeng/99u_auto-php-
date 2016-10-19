<?php
require_once "im_uc.php";
$user_id = '741007';

// 送花列表
// 服务器localhost不识别，换成127.0.0.1
$conn = mysqli_connect('127.0.0.1', 'root', 'root') or die("error connecting");
mysqli_select_db($conn, 'im');
$sql = "select * from send WHERE status =1";
$result = mysqli_query($conn, $sql);
$return = [];
while ($row = mysqli_fetch_assoc($result)) {
    $return[] = $row;
}
// PDO
//$pdo = new PDO('mysql:host=127.0.0.1;dbname=im;port=3306', 'root', 'root');
//$pdo->exec('set names utf8');
//
//$stmt = $pdo->prepare("select * from send");
//$stmt->execute();
//$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

//登陆
im_uc::$_mac_token = im_uc::token($user_id, "ZFLzfl123");

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

