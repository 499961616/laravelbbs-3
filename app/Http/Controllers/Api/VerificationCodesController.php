<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\VerificationCodeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request,EasySms $easySms)
    {
        $phone = $request->phone;


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
