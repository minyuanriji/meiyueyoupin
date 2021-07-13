<?php
class AppUtil{
	/**
	 * 将参数数组签名
	 */
	public static function SignArray(array $array,$appkey){
		// $array['key'] = $appkey;// 将key放到数组中一起进行排序和组装
		// ksort($array);
		$blankStr = AppUtil::ToSignParams($array);
		$sign = md5($blankStr.'&'.$appkey);
		return $sign;
	}
	
	public static function ToUrlParams(array $array)
	{
		$buff = "";
		foreach ($array as $k => $v)
		{
			if(!is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}
	public static function ToSignParams(array $array)
	{
		$buff = "&";
		foreach ($array as $k => $v)
		{
			if(!is_array($v)){
				$buff .=  $v . "&";
			}
		}
		
		$buff = rtrim($buff, "&");
		return $buff;
	}
	
/**
	 * 校验签名
	 * @param array 参数
	 * @param unknown_type appkey
	 */
	public static function ValidSign(array $array,$appkey){
		$sign = $array['sign'];
		unset($array['sign']);
		$array['key'] = $appkey;
		$mySign = AppUtil::SignArray($array, $appkey);
		return strtolower($sign) == strtolower($mySign);
	}
	
	/**
	*发送请求操作仅供参考,不为最佳实践
	* @param url 地址
	* @param params
	*/
	public static function Request($url,$params){
		$ch = curl_init();
		$this_header = array("content-type: application/x-www-form-urlencoded;charset=UTF-8");
		curl_setopt($ch,CURLOPT_HTTPHEADER,$this_header);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		 
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);//如果不加验证,就设false,商户自行处理
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		 
		$output = curl_exec($ch);
		curl_close($ch);
		return  $output;
	}
}
?>