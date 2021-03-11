<?php
/**
 * Helper.php
 * Created by Lxd.
 * QQ: 790125098
 */

namespace Pearton\toolZone;

use Exception;

class Helper
{
    const MOBILE_SEGMENT = "https://tcc.taobao.com/cc/json/mobile_tel_segment.htm";

    /**
     * 获取手机归宿地信息数组
     * @param string $phone
     * @param string $returnType
     * @return array|bool|mixed|string
     */
    public function getMobileSegment(string $phone,string $returnType = 'arr')
    {
        $requestUrl = self::MOBILE_SEGMENT.'?tel='.$phone;
        $request = $this->httpRequest($requestUrl,'GET');

        $return = json_decode($request,true);
        if($return == null){
            try{
                $request = trim(explode('=',$request)[1]);
                $encode = mb_detect_encoding($request, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
                if($encode !== 'UTF-8'){
                    $request = mb_convert_encoding($request, 'UTF-8', $encode);
                }
                preg_match_all("/(\w+):'([^']+)/", $request, $m);
                $return = array_combine($m[1], $m[2]);
            }catch (Exception $exception){
                $return = false;
            }
        }
        switch ($returnType){
            case 'string':
                try {
                    $return = $return['province'].'|'.$return['catName'];
                }catch (Exception $exception){
                    $return = "查询失败";
                }
                break;
        }
        return $return ?: false;
    }

    /**
     * CURL请求
     * @param $url |请求url地址
     * @param $method |请求方法 get post
     * @param null $postfields post数据数组
     * @param array $headers 请求header信息
     * @param bool|false $debug  调试开启 默认false
     * @return mixed
     */
    public function httpRequest(
        string $url,
        string $method="GET",
        $postfields = null,
        array $headers = array(),
        bool $debug = false
    ) {
        $method = strtoupper($method);
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
        curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
                break;
            default:
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
                break;
        }
        $ssl = preg_match('/^https:\/\//i',$url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if($ssl){
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        }
        //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
        //curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
        $response = curl_exec($ci);
        $requestinfo = curl_getinfo($ci);
        /** @noinspection PhpUnusedLocalVariableInspection */
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        if ($debug) {
            echo "=====post data======\r\n";
            var_dump($postfields);
            echo "=====info===== \r\n";
            print_r($requestinfo);
            echo "=====response=====\r\n";
            print_r($response);
        }
        curl_close($ci);
        return $response;
        //return array($http_code, $response,$requestinfo);
    }

    /**
     * 富文本提取
     * Created by Lxd
     * @param $value
     * @param int $limit
     * @param string $code
     * @return string
     * @noinspection PhpUnused
     */
    public function GetContentByRichText($value,$limit = 100,$code = 'utf-8'){
        $html_string = htmlspecialchars_decode($value);
        $content = str_replace(" ", "", $html_string);
        $contents = strip_tags($content);
        return mb_substr($contents, 0, $limit, $code);
    }

    /**
     * 多维数组转字符串[保留html结构]
     * @param $arr
     * @param int $t
     * @return string|null
     * @noinspection PhpUnused
     */
    public function arr2str($arr,$t=0){
        if(!is_array($arr)){
            return '非数组结构数据';
        }
        $str = null;
        $pad = str_pad("",$t,"\t");
        foreach($arr as $k=>$v){
            if(is_array($v)){
                if(is_string($k)){
                    $str.= $pad."'".$k."'=>array(\n".$this->arr2str($v,$t+1).$pad."),\n";
                }else{
                    $str.= $pad ."array(\n".$this->arr2str($v,$t+1).$pad."),\n";
                }
            }else{
                if(is_string($k)){
                    $str.= $pad."'".$k."'=>'".$v."',\n";
                }else{
                    $str.= $pad."'".$v."',\n";
                }
            }
        }
        return $str;
    }

    /**
     * @param $info
     * @param null $length
     * @return string
     * 提取富文本框文本
     * @noinspection PhpUnused
     */
    public function richTextTochar($info,$length = null){
        $html_string = htmlspecialchars_decode($info);              //把一些预定义的 HTML 实体转换为字符
        $content = str_replace(" ", "", $html_string);              //将空格替换成空
        $contents = strip_tags($content);                           //函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
        if($length !== null) {                                      //返回字符串中的指定长度
            $contents = mb_substr($contents, 0, $length, "utf-8");
        }
        if(strlen($contents) < $length)
            return $contents;
        else
            return $contents.'...';
    }

    /**
     * 获取域名协议
     * @return string
     * @noinspection PhpUnused
     */
    public function get_http_type()
    {
        return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
    }

    /**
     * 获取加密参数
     * @param $params
     * @return string
     * @noinspection PhpUnused
     */
    public function getSign($params){
        return md5(join($params).'Lxdc9uBBgaerNCS9d9RAsljQEoAmGPAf2K1lk7ti+R+8Ro');
    }
}