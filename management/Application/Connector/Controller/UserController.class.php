<?php
namespace Connector\Controller;

use Think\Controller;

class UserController extends Controller{
	public function reg(){
		if(!$_POST['user_phone']||!$_POST['user_password']) {
			$res['result']=0;
			$res['data']="手机号码或者密码不能为空";
		}else{
			$model = D('user_info');
			$data['user_phone']=$_POST['user_phone'];
			$data['user_cid']=$_POST['user_cid'];
			// 用户注册之前先对密码加密
			$data['user_password']=md5($_POST['user_password'].C('MD5_KEY'));
			//用户名生成随机字符串
			$name = new \Org\Util\String();
			$data['user_name']=$name->randString(6,5);
			$data['user_time']=date('Y-m-d H:i:s');
			//设置默认头像
			$data['user_img']='User/user_img.png';
			$findmess['user_phone']=$_POST['user_phone'];
			$user=$model->where($findmess)->find();
			//先判断该手机是否注册过
			if ($user) {
				//User exists
				$res['result']=0;
				$res['data']="该手机号已经被注册";
			}else{
				//判断验证码是否正确
				$jieguo = M('user_yzm')
				         ->where(array(
				            'user_phone' => array('eq', $_POST['user_phone']),
				            'user_yzm' => array('eq', $_POST['user_yzm'])
				         ))
				         ->find();
				
				
				
				if(!$jieguo){
					$res['result']=0;
					$res['data']="请输入正确验证码";
				}else{
					//判断是否过期
					if(floor((time()-strtotime($jieguo['yzm_time']))%86400/60) >= 5){
						$res['result']=0;
						$res['data']="该验证码已过期，请重新获取";
					}else{
						//注册融云token
						$appKey = 'c9kqb3rdcvq4j';
						$appSecret = 'usuKQXzEY2';
						$RongCloud = new \Im\RongCloud($appKey,$appSecret);
						// 获取 Token 方法
						$rongyun = $RongCloud->user()->getToken($data['user_phone'], 'username', 'http://www.rongcloud.cn/images/logo.png');
						$rongyun = json_decode($rongyun,1);
						$data['im_token'] = $rongyun['token'];
						if($rongyun){
							//写入数据库
							$addres=$model->add($data);
							$res['result']=1;
							$res['data']="恭喜注册成功";
						}
					}
				}
			}
		}

		echo json_encode($res);
	}
	
	public function login(){
		//自动登陆
		if(I('post.user_phone') && I('post.user_token')){
			$data['user_phone']=I('post.user_phone');
			$data['user_token']=I('post.user_token');
			$mess=M('user_info')->where($data)->find();
			if ($mess) {
				//token过期，重新登录
				if(ceil((time() - strtotime($mess['token_time']))/(60*60*24))>=7){
					$res['result']=0;
					$res['data']="您的登录信息已过期，请重新登录";
				}else{
					$res['result']=1;
					$res['data']="自动登录成功";
					$res['user_token']=md5('user_phone'+time());
					$res['user_id']=$mess['user_id'];
					$res['im_token']=$mess['im_token'];
				//	$res['user_name']=$mess['user_name'];
					
					$token['user_token']=$res['user_token'];
					$token['token_time']=date('Y-m-d H:i:s');
					$token['login_time']=date('Y-m-d H:i:s');
					M('user_info')->where($data)->save($token);
				}
			}else{
				$res['result']=0;
				$res['data']="您的登录信息已过期，请重新登录";
			}
		}else{
			//从登录界面登陆
			$data['user_phone']=I('post.user_phone');
			$data['user_password']=md5(I('post.user_password').C('MD5_KEY'));
			$mess=M('user_info')->where($data)->find();
			if ($mess) {
				$res['result']=1;
				$res['data']="登录成功";
				$res['user_token']=md5('user_phone'+time());
				$res['user_id']=$mess['user_id'];
				$res['im_token']=$mess['im_token'];
				//$res['user_name']=$mess['user_name'];
				
				$token['user_token']=$res['user_token'];
				$token['token_time']=date('Y-m-d H:i:s');
				$token['login_time']=date('Y-m-d H:i:s');
				M('user_info')->where($data)->save($token);
				
			}else{
				$res['result']=0;
				$res['data']="用户名或密码错误";
			}
		}

		echo json_encode($res);
	}
	
	public function getuserInfo(){
			$data['user_phone']=I('post.user_phone');
			$res=M('user_info')->where($data)->find();
			$ic = C('IMAGE_CONFIG');
			$res['user_img']=$ic['viewPath'].$res['user_img'];
			$res['result']=1;
			
			echo json_encode($res);
	}
	
	//头像修改
	public function headimg(){
//		$ic = C('IMAGE_CONFIG');
//		$data['imgData']=I('post.imgData');
//		$phone['user_phone']=I('post.user_phone');
//		$img = base64_decode($data['imgData']);
//		$path = './Public/Uploads/User/headimg/';
//		$imgname=uniqid().'.png';
//		$zijie = file_put_contents($path.$imgname, $img);//返回的是字节数
//		if($zijie){
//			$res['result']=1;
//			$res['imgurl']=$ic['viewPath'].'User/headimg/'.$imgname;
//			//对用户表进行操作更换头像
//			$saveimg['user_img'] = 'User/headimg/'.$imgname;
//			$saveres=M('user_info')->where($phone)->save($saveimg);
//			
//		}else{
//			$res['result']=0;
//		}
//		echo json_encode($res);
		if(!empty($_POST)){
			$data['user_phone']=I('post.user_phone');
			foreach ( $_FILES as $name=>$file ) {
				if($file['error']==0){
					 $cfg = array(
	                   'rootPath' => './Public/Uploads/User/headimg/',
	               );
	               $up = new \Think\Upload($cfg);
	               $z = $up -> uploadOne($file);
				   $path = 'User/headimg/'.$z['savepath'].$z['savename'];
				   $saveimg['user_img']=$path;
				   //上传成功，把头像路径写入数据库
				   if($z){
				   		M('user_info')->where($data)->save($saveimg);
					    $res['result']=1;
				   }
				}else{
					$res['result']=0;
				}
			}
        }else{
        	$res['result']=0;
        }
		echo json_encode($res);
	}


    //获取知识推送列表
    public function getKnowList()
    {
        $model = D('health_know');
        $data  = $model
        ->order('know_see desc')
        ->limit(5)
        ->select();
        foreach ($data as $k => $v) {
            $data[$k]['know_content'] = htmlspecialchars_decode($v['know_content']);
        }
        echo json_encode($data);
    }

	//找回密码
	public function forgetpwd(){
		//判断验证码是否正确
		$jieguo = M('user_yzm')
		         ->where(array(
		            'user_phone' => array('eq', $_POST['user_phone']),
		            'user_yzm' => array('eq', $_POST['user_yzm'])
		         ))
		         ->find();
				
		if(!$jieguo){
			$res['result']=0;
			$res['data']="请输入正确验证码";
		}else{
			//判断是否过期
			if(floor((time()-strtotime($jieguo['yzm_time']))%86400/60) >= 5){
				$res['result']=0;
				$res['data']="该验证码已过期，请重新获取";
			}else{
				$res['result']=1;
				$res['data']="验证码正确";
				
			}
		}
		echo json_encode($res);
	}
	
	//修改密码
	public function reset(){
		$data['user_phone']=I('post.user_phone');
		$save['user_password']=md5($_POST['user_password'].C('MD5_KEY'));
		$mess=M('user_info')->where($data)->save($save);
		if($mess){
			$res['result']=1;
			$res['data']='恭喜！修改成功';
		}else{
			$res['result']=0;
			$res['data']='修改失败，请检查您的网络';
		}
		echo json_encode($res);
	}
	
	//我的医生
	public function mydoc(){
		$data['user_phone']=I('post.user_phone');
		$data['doc_phone']=I('post.doc_phone');
		//如何有记录则不操作，无记录就添加
		$findres = M('my_doc')->where($data)->find();
		if($findres){
			$res['result']=0;
		}else{
			$data['my_doc_time']=date('Y-m-d H:i:s');
			$res=M('my_doc')->add($data);
			if($res){
				$res['result']=1;
			}else{
				$res['result']=0;
			}
		}
		
		echo json_encode($res);
	}
	
	//获取健康知识详情
    public function getKnowDetail()
    {
        $knowID            = I('get.knowID');
        $model             = D('health_know');
        $data              = $model->find($knowID);
        $_POST['know_see'] = $data['know_see'] + 1;
        $_POST['know_id']  = $knowID;
        $info = $model -> create(I('post.'),1);
        $model->save();
        $data['know_content'] = htmlspecialchars_decode($data['know_content']);
        echo json_encode($data);
    }

    //意见反馈
    public function feedback(){
        $user_phone = I('post.phone');
        $userModel = D('user_info');
        $data = $userModel->field('user_id')->where(array(
            'user_phone' => array('eq',$user_phone)
            ))->find();
        $model = D('feedback');
        $_POST['feedb_time'] = date('Y-m-d H:i:s');
        $_POST['user_id'] = $data['user_id'];
        //echo json_encode($_POST);
        if($model->create(I('post.'),1)){
            if($model->add()){
                $result['result'] = 1;
                echo json_encode($result);
            }
        }else{
            $result['result'] = 0;
            echo json_encode($result);
        }
    }
}