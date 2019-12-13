<?php


namespace Pay\Controller;


use Common\Controller\HgzCmsController;
use Think\Log;

class AlipayController extends HgzCmsController
{
    private $aop;
    protected function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub

        require './Application/Pay/Conf/aliConfig.php';
        vendor("Alipay.aop.AopClient"); //引入sdk
        $aop = new \AopClient();
        $aop->gatewayUrl = $config['gatewayUrl'];
        $aop->appId = $config['app_id'];
        $aop->rsaPrivateKey = $config['merchant_private_key'];
        $aop->alipayrsaPublicKey =  $config['alipay_public_key'];
        $aop->apiVersion = '1.0';
        $aop->postCharset=$config['charset'];
        $aop->format='json';
        $aop->signType=$config['sign_type'];
        $this->aop = $aop;

//        vendor("Alipay.aop.AopCertClient"); //引入sdk
//        $aop = new \AopCertClient();
//        $appCertPath = SITE_PATH."/Application/Pay/Conf/Cert/appCertPublicKey.crt";//"应用证书路径（要确保证书文件可读），例如：/home/admin/cert/appCertPublicKey.crt";
//        $alipayCertPath = SITE_PATH."/Application/Pay/Conf/Cert/alipayCertPublicKey_RSA2.crt";//"支付宝公钥证书路径（要确保证书文件可读），例如：/home/admin/cert/alipayCertPublicKey_RSA2.crt";
//        $rootCertPath = SITE_PATH."/Application/Pay/Conf/Cert/alipayRootCert.crt";//"支付宝根证书路径（要确保证书文件可读），例如：/home/admin/cert/alipayRootCert.crt";
//        $aop->alipayrsaPublicKey = $aop->getPublicKey($alipayCertPath);
//        $aop->isCheckAlipayPublicCert = true;//是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
//        $aop->appCertSN = $aop->getCertSN($appCertPath);//调用getCertSN获取证书序列号
//        $aop->alipayRootCertSN = $aop->getRootCertSN($rootCertPath);//调用getRootCertSN获取支付宝根证书序列号
    }

    //获取支付宝个人信息
    public function get_user_info($callback){
        $date = date("Y-m-d");
        $my_url = urlencode($callback);
        $auth_code = $_REQUEST["auth_code"];//存放auth_code
        if (empty($auth_code)) {
            //state参数用于防止CSRF攻击，成功授权后回调时会原样带回
            $_SESSION['alipay_state'] = md5(uniqid(rand(), TRUE));
            //拼接请求授权的URL
            $url = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=" . $this->aop->appId . "&scope=auth_user&redirect_uri=" . $my_url . "&state=" . $_SESSION['alipay_state'];
            exit("<script> top.location.href='" . $url . "'</script>");
        }
        //Step2: 使用auth_code换取apauth_token
        if ($_REQUEST['state'] == $_SESSION['alipay_state'] || 1) {
            //根据返回的auth_code换取access_token
            vendor("Alipay.aop.request.AlipaySystemOauthTokenRequest");//调用sdk里面的AlipaySystemOauthTokenRequest类
            $request = new \AlipaySystemOauthTokenRequest();
            $request->setGrantType("authorization_code");
            $request->setCode($auth_code);
            $result = $this->aop->execute($request);
//        Log::write(json_encode($result), 'info', '', 'Logs/Pay/Alipay/GetUserInfo/' . $date . '.txt');
            $access_token = $result->alipay_system_oauth_token_response->access_token;
            //Step3: 用access_token获取用户信息
            vendor("Alipay.aop.request.AlipayUserInfoShareRequest");//调用sdk里面的AlipayUserInfoShareRequest类
            $request = new \AlipayUserInfoShareRequest();
            $result = $this->aop->execute($request, $access_token);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
            Log::write(json_encode($result), 'info', '', 'Logs/Pay/Alipay/GetUserInfo/' . $date . '.txt');
            if (!empty($resultCode) && $resultCode == 10000) {
                $user_data = $result->$responseNode;
                $data = array();
                $data['sex'] = $user_data->gender == 'm' ? 1 : 2;
                $data['province'] = is_null($user_data->province) ? '' : $user_data->province;
                $data['city'] = is_null($user_data->city) ? '' : $user_data->city;
                $data['nickname'] = is_null($user_data->nick_name) ? '' : $user_data->nick_name;
                $data['openid'] = $user_data->user_id;
                $data['headimgurl'] = is_null($user_data->avatar) ? '' : $user_data->avatar;
//                Log::write(json_encode($data), 'info', '', 'Logs/Pay/Alipay/GetUserInfo/' . $date . '.txt');
                $user = M("wx_fans")->where(array("openid" => $user_data->user_id))->find();
                if ($user) {
                    $uid = D('WxFans')->getFieldByOpenid($data ['openid'], 'uid');
                    session('uid', $uid);
                    return $uid;
                } else {
                    D('WxFans')->init_fans($data);
                    $uid = D('WxFans')->getFieldByOpenid($data ['openid'], 'uid');
                    if (!D('MemberRel')->getByUid($uid)) {
                        $pid = D('WxFans')->where(array('uid' => (int)$_GET['invite_pid']))->find() ? (int)$_GET['invite_pid'] : 0;
                        if ($pid) {
                            $sub = D('MemberRel')->getFieldByUid($pid, 'sub');
                            $sub = $uid . ',' . $sub;
                        } else {
                            $sub = $uid;
                        }
                        $fans = array('type' => 4, 'uid' => $uid, 'pid' => $pid, 'sub' => $sub, 'create_time' => time());
                        $result = D('MemberRel')->add($fans);
                    }
                    session('uid', $uid);
                    redirect($callback);
                }
            } else {
                exit('获取个人信息失败！');
            }

        }
    }


    //创建订单
    public function create_pay($data,$model){
        vendor("Alipay.aop.request.AlipayTradeCreateRequest");
        $request = new \AlipayTradeCreateRequest();
        $date = date("Y-m-d");
        Log::write(json_encode($data,JSON_UNESCAPED_UNICODE),'info','','Logs/Pay/Alipay/Create/'.$date.'.txt');
        //红包模式
        if ($model == 1){
            $notify_url = $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . '/Pay/Alipay/return_url_hb';
            $returnUrl = U('index/hongbao',array('order_no'=>$data['out_trade_no']));//跳转地址
        }else{
            $notify_url = $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . '/Pay/Alipay/return_url_md';
            $returnUrl = U('index/index');//跳转地址
        }
        $request->setNotifyUrl($notify_url);
        $request->setBizContent(json_encode($data));
        $result = $this->aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $res =  $result->$responseNode;
        $res->url = $returnUrl;
        $ress = json_encode($res,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        Log::write($ress,'info','','Logs/Pay/Alipay/Create/'.$date.'.txt');
        $this->ajaxReturn($res,'JSON');
    }

    //红包模式支付回调
    public function return_url_hb(){
        $data = $_POST;
        $date = date("Y-m-d");
        Log::write(json_encode($data,JSON_UNESCAPED_UNICODE),'info','','Logs/Pay/Alipay/PayCallBack/HongBao/'.$date.'.txt');
        //验签
        $restt = $this->aop->rsaCheckV1($data,'',$data['sign_type']);
        if ($restt === true){
            if($data['trade_status'] == 'TRADE_SUCCESS'){
                $uid = M('wx_fans')->where(['openid'=>$data['buyer_id']])->getField('uid');
                $where['status'] = 1;
                $where['uid']=$uid;
                $where['order_no']=$data['out_trade_no'];
                $map['status'] = 2;
                $map['pay_status'] = 1;
                $map['type'] = 3;//1排单模式待免单 2排单模式已免单 3红包模式
                $map['pay_time'] = strtotime($data['notify_time']) ;
                $map['trade_no'] = $data['trade_no'];
                $re=M('turnover')->where($where)->save($map);
                if($re>0){
                    $where['status'] = 2;
                    $ids=M('turnover')->where($where)->getField('id',true);
                    $formname = 'Mall' . '/' . 'turnover';
                    foreach ($ids as $key => $value) {
                        $mallOrder = D($formname);
                        $mallOrder->order_tx($value);
                    }
                }
                $result = 'success';
                exit($result);
            }
        }
    }

    //免单模式支付回调
    public function return_url_md(){
        $data = $_POST;
        $date = date("Y-m-d");
        Log::write(json_encode($data,JSON_UNESCAPED_UNICODE),'info','','Logs/Pay/Alipay/PayCallBack/MianDan/'.$date.'.txt');
        //验签
        $restt = $this->aop->rsaCheckV1($data,'',$data['sign_type']);
        if ($restt === true){
            if($data['trade_status'] == 'TRADE_SUCCESS'){
                $uid = M('wx_fans')->where(['openid'=>$data['buyer_id']])->getField('uid');
                $where['status'] = 1;
                $where['uid']=$uid;
                $where['order_no']=$data['out_trade_no'];
                $map['status'] = 2;
                $map['pay_status'] = 1;
                $map['type'] = 1;//1排单模式待免单 2排单模式已免单 3红包模式
                $map['pay_time'] = strtotime($data['notify_time']);
                $map['trade_no'] = $data['trade_no'];
                $re=M('turnover')->where($where)->save($map);
                if($re>0){
                    $where['status'] = 2;
                    $ids=M('turnover')->where($where)->getField('id',true);
                    $formname = 'Mall' . '/' . 'turnover';
                    foreach ($ids as $key => $value) {
                        $mallOrder = D($formname);
                        $mallOrder->order_tx($value);
                    }
                }
                $result = 'success';
                exit($result);
            }
        }

    }

    //退款
    public function refund($free){
        vendor("Alipay.aop.request.AlipayTradeRefundRequest");
        $request = new \AlipayTradeRefundRequest();
        $date = date("Y-m-d");
        $data['out_trade_no'] = $free['order_no'];
        $data['trade_no'] = $free['trade_no'];
        $data['refund_amount'] = $free['order_amount'];
        Log::write(json_encode($data,JSON_UNESCAPED_UNICODE),'info','','Logs/Pay/Alipay/Refund/'.$date.'.txt');
        $request->setBizContent(json_encode($data));
        $result = $this->aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $res =  $result->$responseNode;
        $ress = json_encode($res,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        Log::write($ress,'info','','Logs/Pay/Alipay/Refund/'.$date.'.txt');
        $resultCode = $res->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            $seller_id = M('turnover')->where(['order_no'=>$res->out_trade_no])->getField('seller_id');//获取订单中商户id
            $shop = M('product_seller')->where(['id'=>$seller_id])->find();//获取商户信息
            $data['jiangchi'] = $shop['jiangchi'] - ($res->refund_fee);//计算奖池
            M('product_seller')->where(['id'=>$seller_id])->save($data);    //更新商户奖池
            $pt['refund_time'] = strtotime($res->gmt_refund_pay);
            $pt['type'] = 2;
            $pt['refund_status'] = 1;
            $pt['refund_des'] = '退款成功，免单成功';
            $pt['refund_id'] = $res->refund_settlement_id;
            $where['order_no'] = $res->out_trade_no;
            $where['trade_no'] = $res->trade_no;
            $rest = M('turnover')->where($where)->save($pt);
            R('Pay/Huijupay/toaddWealth',array('order_no'=>$res->out_trade_no));
            return true;
        } else {
            $pt['refund_time'] = time();
            $pt['refund_status'] = 2;//退款失败
            $pt['refund_des'] = $res->sub_msg;
            M('turnover')->where(['order_no'=>$free['order_no']])->save($pt);
            return false;
        }
    }

    //支付宝生活号激活开发者网关
    public function gateway()
    {
        $data = $_POST;
        $date = date("Y-m-d");
        $sign_verify = $this->aop->rsaCheckV2($data, '', $data['sign_type']);
        if (!$sign_verify) {
            // 如果验证网关时，请求参数签名失败，则按照标准格式返回，方便在服务窗后台查看。
            if ($data["service"] == "alipay.service.check") {
                $this->verifygw(false,$data["biz_content"]);
                Log::write('验签成功', 'info', '', 'Logs/Pay/Alipay/ShengHuoHao/' . $date . '.txt');
            } else {
                Log::write('验签失败', 'info', '', 'Logs/Pay/Alipay/ShengHuoHao/' . $date . '.txt');
            }
            exit ();
        }
        // 验证网关请求
        if ($data["service"] == "alipay.service.check") {
            $this->verifygw(true, $data["biz_content"]);
        } else if ($data["service"] == "alipay.mobile.public.message.notify") {
            // 处理收到的消息
            $this->Message($data['biz_content']);
        }
    }


    public function verifygw($is_sign_success,$biz_content=null) {
        $disableLibxmlEntityLoader = libxml_disable_entity_loader(true);
        $xml = simplexml_load_string ( $biz_content );
        libxml_disable_entity_loader($disableLibxmlEntityLoader);
        // print_r($xml);
        $EventType = ( string ) $xml->EventType;
        // echo $EventType;
        if ($EventType == "verifygw") {
            if ($is_sign_success) {
                $response_xml = "<success>true</success><biz_content>" . $this->aop->rsaPrivateKey . "</biz_content>";
            } else { // echo $response_xml;
                $response_xml = "<success>false</success><error_code>VERIFY_FAILED</error_code><biz_content>" .  $this->aop->merchant_private_key . "</biz_content>";
            }
            $mysign=$this->aop->alonersaSign($response_xml,$this->aop->rsaPrivateKey,$this->aop->signType);
            $return_xml = "<?xml version=\"1.0\" encoding=\"".$this->aop->postCharset."\"?><alipay><response>".$response_xml."</response><sign>".$mysign."</sign><sign_type>".$this->aop->signType."</sign_type></alipay>";
            $date = date("Y-m-d");
            Log::write($return_xml, 'info', '', 'Logs/Pay/Alipay/ShengHuoHao/' . $date . '.txt');
            echo $return_xml;
            exit ();
        }
    }

    //生活号事件类型判断
    public function Message($biz_content)
    {
        $date = date("Y-m-d");
        Log::write($biz_content, 'info', '', 'Logs/Pay/Alipay/ShengHuoHao/' . $date . '.txt');
        header("Content-Type: text/xml;charset=utf-8");
        $UserInfo = $this->getNode($biz_content, "UserInfo");
        $FromUserId = $this->getNode($biz_content, "FromAlipayUserId");
        $AppId = $this->getNode($biz_content, "AppId");
        $CreateTime = $this->getNode($biz_content, "CreateTime");
        $MsgType = $this->getNode($biz_content, "MsgType");
        $EventType = $this->getNode($biz_content, "EventType");
        $AgreementId = $this->getNode($biz_content, "AgreementId");
        $ActionParam = $this->getNode($biz_content, "ActionParam");
        $AccountNo = $this->getNode($biz_content, "AccountNo");
        if ($EventType == 'follow') {
         $where['openid'] = $FromUserId;
         $res = M('wx_fans')->where($where)->find();
         if($res){
             $data['status'] = 1;
             $data['subscribe_time'] = substr($CreateTime,0,10);
             M('wx_fans')->where($where)->save($data);
         }else{
//             get_ali_uid();
//             $where['openid'] = $FromUserId;
//             $res = M('wx_fans')->where($where)->find();
//             if($res){
//                 $data['status'] = 1;
//                 $data['subscribe_time'] = substr($CreateTime,0,10);
//                 M('wx_fans')->where($where)->save($data);
//             }
         }

        }else if(($EventType == 'unfollow')){
            $where['openid'] = $FromUserId;
            $res = M('wx_fans')->where($where)->find();
            if($res){
                $data['status'] = -1;
                $data['subscribe_time'] = 0;
                M('wx_fans')->where($where)->save($data);
            }
        }
        echo self::mkAckMsg ( $FromUserId );
        exit ();
    }

    public function mkAckMsg($toUserId) {
        $response_xml = "<XML><ToUserId><![CDATA[" . $toUserId . "]]></ToUserId><AppId><![CDATA[" . $this->aop->appId . "]]></AppId><CreateTime>" . time () . "</CreateTime><MsgType><![CDATA[ack]]></MsgType></XML>";
        $mysign=$this->aop->alonersaSign($response_xml,$this->aop->merchant_private_key,$this->aop->signType);
        $return_xml = "<?xml version=\"1.0\" encoding=\"".$this->aop->postCharset."\"?><alipay><response>".$response_xml."</response><sign>".$mysign."</sign><sign_type>".$this->aop->signType."</sign_type></alipay>";
        return $return_xml;
    }

    /**
     * 直接获取xml中某个结点的内容
     *
     */
    public function getNode($biz_content, $node) {
        $xml = "<?xml version=\"1.0\" encoding=\"GBK\"?>" . $biz_content;
        $dom = new \DOMDocument ( "1.0", "GBK" );
        $dom->loadXML ( $xml );
        $event_type = $dom->getElementsByTagName ( $node );
        return $event_type->item ( 0 )->nodeValue;
    }

}