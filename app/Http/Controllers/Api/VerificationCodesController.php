<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\VerificationCodeRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request,EasySms $easySms)
    {
        $captchaData = Cache::get($request->captcha_key);

        if (!$captchaData){
            abort(403, '图片验证码已失效');
        }
        if (!hash_equals($captchaData['code'],$request->captcha_code)){
            // 验证错误就清除缓存
            Cache::forget($request->captcha_key);
            throw new AuthenticationException('验证码错误');
        }

        $phone = $captchaData['phone'];

        if (!app()->environment('production')){
            $code = '1234';
        }else{
            //生成四位数验证码
            $code = str_pad(random_int(1,9999),4,0,STR_PAD_LEFT);

            try {
                    $result = $easySms->send($phone,[
                        'template' => config('easysms.gateways.aliyun.templates.register'),
                        'data' => [
                            'code' => $code
                        ],
                    ]);
            }catch (NoGatewayAvailableException $exception){

                    $message = $exception->getException('aliyun')->getMessage();
                    abort('500',$message ?:'短信发送异常');
            }
        }

        $key = 'verificationCode_'.Str::random(15);
        $expiredAt = now()->addMinutes(5);

        //验证码缓存5分钟
        Cache::put($key,['phone'=>$phone,'code'=>$code],$expiredAt);

        return response()->json([
//            'code'=>$code,
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
        ])->setStatusCode(201);

    }
}
//微信登录
//https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxe2bbf375fbc790a8&redirect_uri=http://laravel2.test&response_type=code&scope=snsapi_base&state=123#wechat_redirect
//https://api.weixin.qq.com/sns/oauth2/access_token?appid=wxe2bbf375fbc790a8&secret=c56388fb115b7323921a9d4814c6ece3&code=091Ur4ml2Q5er74govnl2szVzt0Ur4m2&grant_type=authorization_code
//https://api.weixin.qq.com/sns/userinfo?access_token=47_0ZFNUORANnbPyESYiFXUqANM3F21lZXzWSaWyxn2C4G7Jqk4weTO9ReOZO-RjP5u6PxUUCX2ZaAY6MaVrMXe9A&openid=o_G_X5xcn4wg10FF9rMJV2CorWY0&lang=zh_CN
