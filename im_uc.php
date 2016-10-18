<?php
require_once "request.php";

/**
 * IM UC认证方式接口
 * @author 陈梅妹
 * @copyright CopyRight(c) 福建天晴数码有限公司
 * @package bus
 */
final class im_uc {

    /**
     * 头部格式
     * @var string
     */
    private static $_content_type = 'Content-Type:application/json; charset=utf-8';

    public static $_mac_token = '';

    /**
     * 生成唯一ID
     * @author 陈梅妹
     * @param string $prefix 前缀
     * @return string 如果没有前缀的话，是16位。大概是每秒1000并发的时候。有1/3839000 的概率重复
     */
    public static function uuid($prefix = '') {
        return uniqid($prefix) . sprintf('%x', mt_rand(256, 4095));
    }

    /**
     * 签名
     * @author 陈梅妹
     * @param array $req
     * @param array $api
     * @param array $host
     * @return string
     */
    public static function _sign($req, $api, $host) {
        request::$host = $host;

        list($usec, $sec) = explode(" ", microtime());
        $time = $sec . substr($usec, 2, 3);

        //授权
        $authorization = '';
        $mac_token = self::$_mac_token;
        if (!empty($mac_token)) {
            $nonce = $time . ':' . substr(self::uuid(), -8);
            $mac = base64_encode(
                hash_hmac(
                    'sha256',
                    $nonce . "\n" . strtoupper($req) . "\n" . $api . "\n" . $host . "\n",
                    $mac_token['mac_key'],
                    true
                )
            );

            $authorization = 'Authorization: MAC id="' . $mac_token['access_token'] . '",nonce="' . $nonce . '",mac="' . $mac . '"';
        }

        return $authorization;
    }

    /**
     * 加密
     * @author 陈梅妹
     * @param string $password
     * @param string $salt
     * @return string
     */
    private static function _encrypt_md5($password, $salt) {
        $s = '';
        for ($i = 0, $n = strlen($salt); $i < $n; $i++) {
            $s .= ord($salt[$i]) > 127 ? $salt[$i] . $salt[++$i] . $salt[++$i] : $salt[$i];
        }

        return md5($password . $s);
    }

    /**
     * 获取令牌
     * @author 陈梅妹
     * @param string $account
     * @param string $pwd
     * @return string
     */
    public static function token($account, $pwd) {
        $api = '/v0.93/tokens';
        $host = 'aqapi.101.com';

        // 前面加中文逗号、中文句号的 salt 值，再用 md5 生成 UC 中心的密码值
        $salt = mb_convert_encoding('，。fdjf,jkgfkl', 'GBK', 'UTF-8');
        $pwd = self::_encrypt_md5($pwd, $salt);

        request::$host = $host;
        $ret = request::post(
            json_encode(
                array(
                    "login_name" => $account,
                    "password" => $pwd,
                    "org_name" => "ND"
                )
            ),
            'json',
            'file',
            'https://' . $host . $api,
            array(self::$_content_type)
        );
        $tmp = $ret['body'];
        if (isset($tmp['user_id'])) {
            $mac_token = array(
                'access_token' => $tmp['access_token'],
                'mac_key' => $tmp['mac_key'],
                'mac_algorithm' => $tmp['mac_algorithm']
            );
        } else {
            $mac_token = array();
        }
        return $mac_token;
    }

    /**
     * 获取群组信息
     * @author 陈梅妹
     * @param string $account
     * @param int $start
     * @param int $size
     * @return array
     */
    public static function groups($account, $start, $size) {
        $api = '/v0.2/entities/' . $account . '/groups?$offset=' . $start . '&$limit=' . $size;
        $host = 'im-group.web.sdp.101.com';

        $authorization = self::_sign('GET', $api, $host);

        if (empty($authorization)) {
            return false;
        }

        $ret = request::get(
            array(),
            'json',
            'http://' . $host . $api,
            array(
                self::$_content_type,
                $authorization
            )
        );

        return $ret['body'];
    }

    /**
     * 登陆
     * @param string $account
     * @param string $pwd
     * @return string
     */
    public static function login($account, $pwd) {
        $api = '/v0.93/tokens';
        $host = 'aqapi.101.com';

        // 前面加中文逗号、中文句号的 salt 值，再用 md5 生成 UC 中心的密码值
        $salt = mb_convert_encoding('，。fdjf,jkgfkl', 'GBK', 'UTF-8');
        $pwd = self::_encrypt_md5($pwd, $salt);
        request::$host = $host;
        $ret = request::post(
            json_encode(
                array(
                    "login_name" => $account,
                    "password" => $pwd,
                    "org_name" => "ND"
                )
            ),
            'json',
            'file',
            'https://' . $host . $api,
            array(self::$_content_type)
        );
        return $ret['body'];

    }

    /**
     * 获取用户信息
     * @param $user_id
     * @return mixed
     * @throws \Exception
     */
    public static function get_user_info($user_id) {
        $api = '/v0.3/users/' . $user_id;
        $host = 'aqapi.101.com';

        $authorization = self::_sign('GET', $api, $host);

        if (empty($authorization)) {
            return false;
        }

        request::$host = $host;
        $ret = request::get(
            array(),
            'json',
            'https://' . $host . $api,
            array(self::$_content_type, $authorization)
        );

        return $ret['body'];

    }

    /**
     * 获取工号列表
     * @author 葛剑辉
     * @param string $keyword
     * @param int $start
     * @param int $size
     * @return array
     */
    public static function muti_gh($keyword, $start, $size) {

        // 获取组织ID
        $org_id = self::_get_org_id();
        $keyword = urlencode($keyword);

        $api = '/v0.9/organizations/' . $org_id . '/orgnodes/0/users/actions/search?name=' . $keyword . '&$offset=' . $start . '&$limit=' . $size;
        $host = 'aqapi.101.com';

        $authorization = self::_sign('GET', $api, $host);

        if (empty($authorization)) {
            return false;
        }

        $ret = request::get(
            array(),
            'json',
            'https://' . $host . $api,
            array(
                self::$_content_type,
                $authorization
            )
        );

        return $ret['body'];
    }

    /**
     * 获取组织id
     * @author 葛剑辉
     * @return String
     */
    private static function _get_org_id() {
        $api = '/v0.9/organizations/actions/query';
        $host = 'aqapi.101.com';

        request::$host = $host;
        $authorization = self::_sign('POST', $api, $host);

        $ret = request::post(
            json_encode(
                array(
                    "org_name" => "ND"
                )
            ),
            'json',
            'file',
            'https://' . $host . $api,
            array(self::$_content_type, $authorization)
        );


        return $ret['body']['org_id'];
    }

    /**
     * 获取用户信息
     * @param $user_id
     * @return mixed
     * @throws \Exception
     */
    public static function birthday_users() {
        $api = '/v0.1/birthday_users';
        $host = 'im-birthday.social.web.sdp.101.com';

        $authorization = self::_sign('GET', $api, $host);

        if (empty($authorization)) {
            return false;
        }

        request::$host = $host;
        $ret = request::get(
            array(),
            'json',
            'http://' . $host . $api,
            array(self::$_content_type, $authorization)
        );

        return $ret['body'];

    }

    /**
     * 获取用户信息
     * @param $user_id
     * @return mixed
     * @throws \Exception
     */
    public static function bless($user_id) {
        $api = "/v0.1/birthday_users/{$user_id}/actions/bless";
        $host = 'im-birthday.social.web.sdp.101.com';

        $authorization = self::_sign('POST', $api, $host);

        if (empty($authorization)) {
            return false;
        }

        request::$host = $host;
        $ret = request::post(
            array(),
            'json',
            'fields',
            'http://' . $host . $api,
            array(self::$_content_type, $authorization)
        );

        return $ret['body'];

    }

    /**
     * 获取用户信息
     * @param $user_id
     * @return mixed
     * @throws \Exception
     */
    public static function send_flower($user_id) {
        $api = "/v0.3/c/flower/send";
        $host = 'pack.web.sdp.101.com';

        $authorization = self::_sign('POST', $api, $host);

        if (empty($authorization)) {
            return false;
        }

        request::$host = $host;
        $ret = request::post(
            json_encode(
                array(
                    "dest_uid" => $user_id,
                    "item_type_id" => 20000,
                    'amount' => 1
                )
            ),
            'json',
            'file',
            'http://' . $host . $api,
            array(self::$_content_type, $authorization)
        );

        return $ret['body'];

    }

    /**
     * 获取用户信息
     * @param $user_id
     * @return mixed
     * @throws \Exception
     */
    public static function get_receive_point_list($user_id) {
        request::$host = 'mobile.ioa.99.com';

        $ret = request::get(
            array('userID' => $user_id),
            'json',
            'http://mobile.ioa.99.com/ServiceHost/ToDoList/json/getReceivePointList',
            array(self::$_content_type, 'Nd-CompanyOrgId: 481036337156')
        );

        return $ret['body'];
    }

    /**
     * 获取用户信息
     * @param $user_id
     * @param $auto_code
     * @return mixed
     * @throws err
     */
    public static function receive_point($user_id, $auto_code) {
        request::$host = 'mobile.ioa.99.com';

        $ret = request::get(
            array(
                'userID' => $user_id,
                'auto' => $auto_code,
                'sid' => ''
            ),
            'json',
            'http://mobile.ioa.99.com/ServiceHost/ToDoList/json/receivePoint',
            array(self::$_content_type, 'Nd-CompanyOrgId: 481036337156')
        );

        return $ret['body'];
    }

    public static function getgrowinfo($user_id) {
        request::$host = 'ioa.99.com';

        $ret = request::get(
            array(
                'uid' => $user_id,
            ),
            'jso1n',
            'http://' . request::$host . '/ERPDesk/Assist_Default.aspx',
            array(self::$_content_type, 'Nd-CompanyOrgId: 481036337156')
        );

        return $ret['body'];
    }

    public static function sign_in($user_id) {
        request::$host = 'mobile.ioa.99.com';

        $ret = request::get(
            array(
                'userID' => $user_id,
                'sid' => ''
            ),
            'json',
            'http://mobile.ioa.99.com/ServiceHost/ToDoList/json/signIn',
            array(self::$_content_type, 'Nd-CompanyOrgId: 481036337156')
        );

        return $ret['body'];
    }

    public static function sign_out_new($user_id) {
        request::$host = 'mobile.ioa.99.com';

        $ret = request::get(
            array(
                'userID' => $user_id,
                'sid' => ''
            ),
            'json',
            'http://mobile.ioa.99.com/ServiceHost/ToDoList/json/signOut_new',
            array(self::$_content_type, 'Nd-CompanyOrgId: 481036337156')
        );

        return $ret['body'];
    }

}