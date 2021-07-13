<?php
	// define('IN_MOBILE', true);
	header("Content-type:text/html;charset=utf-8");
	require_once 'AppConfig.php';
	require_once 'AppUtil.php';	
	function pay($paramss){
		$params = array();
		$params["cusid"] = $paramss['mchid'];
	    $params["appid"] = $paramss['appid'];
	    $params["version"] = AppConfig::APIVERSION;
	    $params["trxamt"] = $paramss['fee']*100;//金额（单位：分）
	    $params["reqsn"] = $paramss['tid'];//订单号,自行生成
	    $params["paytype"] = "W02";//W02：微信JS支付;A02：支付宝JS支付;Q02：QQ钱包JS支付

	    $params["randomstr"] = mt_rand();//随机码
	    $params["body"] = $paramss['title'];
	    $params["remark"] = $paramss['remark'];
	    $params["acct"] = $paramss['user'];//用户的微信openid  
	    $params["limit_pay"] = "no_credit";
        $params["notify_url"] = $paramss['notify_url'];//交易结果通知地址
	    $params["sign"] = AppUtil::SignArray($params,$paramss['key']);//签名
	    
	    $paramsStr = AppUtil::ToUrlParams($params);
	    $url = AppConfig::APIURL . "/pay";
	    $rsp = request($url, $paramsStr);
	    // echo "请求参数:".$paramsStr;
	    // echo "<br/>";
	    // echo "请求返回:".$rsp;
	    // echo "<br/>";
	    $rspArray = json_decode($rsp, true); 
	    if(validSign($rspArray,$paramss['key'])){
	    	// echo "验签正确,进行业务处理";
	    	// echo "<br/>";
	    	$wOpt = json_decode($rspArray['payinfo'], true);
	    	// var_dump($wOpt);
	    	// echo "<br/>";
	    	return $wOpt;

	    }
	}
	
	//当天交易用撤销
	function cancel(){
		$params = array();
		$params["cusid"] = AppConfig::CUSID;
	    $params["appid"] = AppConfig::APPID;
	    $params["version"] = AppConfig::APIVERSION;
	    $params["trxamt"] = "1";
	    $params["reqsn"] = "123456788";
	    $params["oldreqsn"] = "123456";//原来订单号
	    $params["randomstr"] = "1450432107647";//
	    $params["sign"] = AppUtil::SignArray($params,AppConfig::APPKEY);//签名
	    $paramsStr = AppUtil::ToUrlParams($params);
	    $url = AppConfig::APIURL . "/cancel";
	    $rsp = request($url, $paramsStr);
		echo "请求返回:".$rsp;
	    echo "<br/>";
	    $rspArray = json_decode($rsp, true); 
	    if(validSign($rspArray)){
	    	echo "验签正确,进行业务处理";
	    }
	}
	
	//当天交易请用撤销,非当天交易才用此退货接口
	function refund(){
		$params = array();
		$params["cusid"] = AppConfig::CUSID;
	    $params["appid"] = AppConfig::APPID;
	    $params["version"] = AppConfig::APIVERSION;
	    $params["trxamt"] = "1";
	    $params["reqsn"] = "1234567889";
	    $params["oldreqsn"] = "123456";//原来订单号
	    $params["randomstr"] = "1450432107647";//
	    $params["sign"] = AppUtil::SignArray($params,AppConfig::APPKEY);//签名
	    $paramsStr = AppUtil::ToUrlParams($params);
	    $url = AppConfig::APIURL . "/refund";
	    $rsp = request($url, $paramsStr);
		echo "请求返回:".$rsp;
	    echo "<br/>";
	    $rspArray = json_decode($rsp, true); 
	    if(validSign($rspArray)){
	    	echo "验签正确,进行业务处理";
	    }
	}
	
	function query(){
		$params = array();
		$params["cusid"] = AppConfig::CUSID;
	    $params["appid"] = AppConfig::APPID;
	    $params["version"] = AppConfig::APIVERSION;
	    $params["reqsn"] = "SH20170511093554084985GJ01";
	    $params["randomstr"] = "1450432107647";//
	    $params["sign"] = AppUtil::SignArray($params,AppConfig::APPKEY);//签名
	    $paramsStr = AppUtil::ToUrlParams($params);
	    $url = AppConfig::APIURL . "/query";
	    $rsp = request($url, $paramsStr);
		echo "请求返回:".$rsp;
	    echo "<br/>";
	    $rspArray = json_decode($rsp, true); 
	    if(validSign($rspArray)){
	    	echo "验签正确,进行业务处理";
	    }
	}
	
	//发送请求操作仅供参考,不为最佳实践
	function request($url,$params){
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
	
	//验签
	function validSign($array,$key){
		if("SUCCESS"==$array["retcode"]){
			$signRsp = strtolower($array["sign"]);
			$array["sign"] = "";
			$sign =  strtolower(AppUtil::SignArray($array, $key));
			if($sign==$signRsp){
				return TRUE;
			}
			else {
				echo "验签失败:".$signRsp."--".$sign;
			}
		}
		else{
			echo $array["retmsg"];
		}
		
		return FALSE;
	}
	
	// pay();
	//cancel();
	//refund();
	// query();
?>