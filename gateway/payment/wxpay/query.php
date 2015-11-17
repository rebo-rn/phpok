<?php
/*****************************************************************************************
	文件： gateway/payment/wxpay/query.php
	备注： 微信订单接口查询
	版本： 4.x
	网站： www.phpok.com
	作者： qinggan <qinggan@188.com>
	时间： 2015年11月09日 04时28分
*****************************************************************************************/
if(!defined("PHPOK_SET")){exit("<h1>Access Denied</h1>");}
class wxpay_query
{
	private $order;
	private $param;
	private $obj;
	public function __construct($order,$param)
	{
		$this->param = $param;
		$this->order = $order;
		$this->paydir = $GLOBALS['app']->dir_root.'gateway/payment/wxpay/';
		$this->baseurl = $GLOBALS['app']->url;
		include "wxpay.php";
		$this->obj = new wxpay_lib();
		$this->obj->app_id($this->param['param']['appid']);
		$this->obj->mch_id($this->param['param']['mch_id']);
		$this->obj->app_key($this->param['param']['app_key']);
		$this->obj->app_secret($this->param['param']['app_secret']);
		$this->obj->ssl_cert($this->param['param']['pem_cert']);
		$this->obj->ssl_key($this->param['param']['pem_key']);
		$this->obj->proxy_host($this->param['param']['proxy_host']);
		$this->obj->proxy_port($this->param['param']['proxy_port']);
	}

	public function submit()
	{
		global $app;
		$data = $this->obj->query($this->order['sn']);
		if(!$data){
			$this->json('查询失败');
		}
		if($data['trade_state'] == 'SUCCESS'){
			$ext = array('openid'=>$data['openid'],'trade_type'=>$data['trade_type'],'bank_type'=>$data['bank_type']);
			$ext['total_fee'] = $data['total_fee'];
			if($data['fee_type']){
				$ext['fee_type'] = $data['fee_type'];
			}
			if($data['cash_fee']){
				$ext['cash_fee'] = $data['cash_fee'];
				if($data['cash_fee_type']){
					$ext['cash_fee_type'] = $data['cash_fee_type'];
				}
			}
			if($data['coupon_fee']){
				$ext['coupon_fee'] = $data['coupon_fee'];
				if($data['coupon_count']){
					$ext['coupon_count'] = $data['coupon_count'];
				}
			}
			$ext['transaction_id'] = $data['transaction_id'];
			$ext['time_end'] = $data['time_end'];
			$tmp = $data['time_end'];
			$time = substr($tmp,0,4).'-'.substr($tmp,4,2).'-'.substr($tmp,6,2).' '.substr($tmp,8,2);
			$time.= ':'.substr($tmp,10,2).':'.substr($tmp,12,2);
			$mytime = strtotime($time);
			if(!$this->order['status']){
				$array = array('status'=>1,'ext'=>serialize($ext));
				$app->model('payment')->log_update($array,$this->order['id']);
			}
			if($this->order['type'] == 'order'){
				$order = $app->model('order')->get_one_from_sn($this->order['sn']);
				if($order){
					$payinfo = $app->model('order')->order_payment($order['id']);
					if($payinfo){
						$payment_data = array('dateline'=>$mytime,'ext'=>serialize($ext));
						$app->model('order')->save_payment($payment_data,$payinfo['id']);
					}else{
						$payment = $app->model('payment')->get_one($this->order['payment_id']);
						$data2 = array('order_id'=>$order['id'],'payment_id'=>$this->order['payment_id']);
						$data2['title'] = $payment['title'];
						$data2['price'] = round($data['total_fee'],100);
						$data2['startdate'] = $mytime;
						$data2['dateline'] = $mytime;
						$data2['ext'] = serialize($ext);
						$app->model('order')->save_payment($data2);
					}
					//更新订单日志
					$app->model('order')->update_order_status($order['id'],'paid');
					$note = P_Lang('订单支付完成，编号：{sn}',array('sn'=>$order['sn']));
					$log = array('order_id'=>$order['id'],'addtime'=>$app->time,'who'=>$app->user['user'],'note'=>$note);
					$app->model('order')->log_save($log);
				}
			}
		}
		if($data['trade_state'] == 'SUCCESS'){
			$array = array('status'=>$data['trade_state']);
			$GLOBALS['app']->json($array,true);
		}else{
			$array = array('status'=>$data['trade_state'],'content'=>$data['trade_state_desc']);
			$GLOBALS['app']->json($array);
		}
	}
}
?>