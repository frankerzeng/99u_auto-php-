<?php

/**
 * 简化http请求
 * @author    欧远宁
 * @version   1.0.0
 * @copyright CopyRight By 欧远宁
 * @package   now
 */
final class request {
	
	public static $RETURN_JSON = 'json';
	public static $RETURN_HTML = 'html';
	public static $POST_JSON = 'json';
	public static $POST_FIELDS = 'fields';
	
	public static $cookie = '';
	public static $url = '';
	public static $host = '';

	/**
	 * 来自kohana的http头解析
	 * @param string $header_string 头文件字符串
     * @return array
     */
	private static function parse_header($header_string) {
		$headers = array();
		
		// Match all HTTP headers
		if (preg_match_all('/(\w[^\s:]*):[ ]*([^\r\n]*(?:\r\n[ \t][^\r\n]*)*)/', $header_string, $matches)) {
			// Parse each matched header
			foreach ( $matches[0] as $key => $value ) {
				$hkey = strtolower($matches[1][$key]);
				
				// If the header has not already been set
				if (! isset($headers[$hkey])) {
					// Apply the header directly
					$headers[$hkey] = $matches[2][$key];
				} 				// Otherwise there is an existing entry
				else {
					// If the entry is an array
					if (is_array($headers[$hkey])) {
						// Apply the new entry to the array
						$headers[$hkey][] = $matches[2][$key];
					} 					// Otherwise create a new array with the entries
					else {
						$headers[$hkey] = array(
							$headers[$hkey], 
							$matches[2][$key] 
						);
					}
				}
			}
		}
		return $headers;
	}

	/**
	 * 发送get请求，得到结果
     * @param array $data   发送的数据
     * @param string $ret_type  返回类型
     * @param string $url 请求的url
     * @param array $append_head 追加的头部
     * @throws \Exception
     * @return array 返回结果
     */
	public static function get($data, $ret_type = 'json', $url='', $append_head=array()) {
		$res = array(
			'header' => '', 
			'body' => '', 
			'code' => 0 
		);
		$url = ($url == '') ? self::$url : $url;
        if (strpos($url, '?') === FALSE) {
            $url.='?'.http_build_query($data);
        } else {
            $url.= http_build_query($data);
        }

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, FALSE);

		$send_header = array(
			'User-Agent:Mozilla/5.0 (Windows NT 5.1; rv:19.0) Gecko/20100101 Firefox/19.0',
			'Connection:close',
			'Host:'.self::$host
		);
		if (self::$cookie) {
			$send_header['Cookie'] = self::$cookie;
		}


		curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($send_header, $append_head));
		
		$response = curl_exec($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$res['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$res['header'] = self::parse_header(substr($response, 0, $header_size));
		$res['body'] = substr($response, $header_size);
		curl_close($ch);
		if ($ret_type == 'json') {
			$res['body'] = json_decode($res['body'], true);
			if (!$res['body']){
				throw new Exception('返回的不是json格式:'.var_export($url,true));
			}
		}
		return $res;
	}

	/**
	 * 发送post请求
     * @param array $data   发送的数据
     * @param string $ret_type  返回类型
     * @param string $post_type  发送请求的形式
     * @param string $url 请求的url
     * @param array $append_head 追加的头部
     * @throws \Exception
     * @return array 返回结果
     */
	public static function post($data, $ret_type = 'json', $post_type = 'fields', $url='', $append_head=array()) {
		$res = array(
			'header' => '', 
			'body' => '', 
			'code' => 0 
		);
		$url = ($url == '') ? self::$url : $url;
		$ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		if($ssl){
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		}
		$send_header = array(
			'User-Agent:Mozilla/5.0 (Windows NT 5.1; rv:19.0) Gecko/20100101 Firefox/19.0',
			'Connection:close',
			'Host:'.self::$host
//            'Referer:http://'.self::$host.'/index.php'
		);
		if (self::$cookie) {
			curl_setopt($ch, CURLOPT_COOKIE, self::$cookie);
		}
		if ($post_type == 'json'){
			$send_header['Content-Type'] = 'application/json; charset=utf-8';
		}
		$post_data = $post_type=='file'?$data:http_build_query($data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($send_header, $append_head));
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		
		$response = curl_exec($ch);

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$res['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$res['header'] = self::parse_header(substr($response, 0, $header_size));
		$res['body'] = substr($response, $header_size);
		curl_close($ch);
		if ($ret_type == 'json') {
            $tmp =  $res['body'];
			$res['body'] = json_decode($res['body'], true);
			if (!$res['body']){
				throw new Exception('返回的不是json格式:'.$tmp);
			}
		}else if($ret_type == 'txt'){
			$res['body'] = '';
		}else if($ret_type == 'tpl'){
			$res['body'] = '';
		}
		return $res;
	}
}