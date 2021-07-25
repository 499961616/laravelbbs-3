<?php

namespace App\Http\Controllers\Api;


use App\Http\Requests\Api\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Cache;

class UsersController extends Controller
{
    public function store(UserRequest $request)
    {
        $verifyData = Cache::get($request->verification_key);

        if (!$verifyData){
            abort(403, '验证码已失效');
        }

        if (!hash_equals($verifyData['code'], $request->verification_code)) {
            // 返回401
            throw new AuthenticationException('验证码错误');
        }

        $user = User::create([
            'name' => $request->name,
            'phone' => $verifyData['phone'],
            'password' => $request->password,
        ]);
        //注册后清除 验证码缓存
        Cache::forget($request->verification_key);

        return new UserResource($user);
    }
}
//微信登录
//https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxe2bbf375fbc790a8&redirect_uri=http://laravel2.test&response_type=code&scope=snsapi_base&state=123#wechat_redirect
//https://api.weixin.qq.com/sns/oauth2/access_token?appid=wxe2bbf375fbc790a8&secret=c56388fb115b7323921a9d4814c6ece3&code=091Ur4ml2Q5er74govnl2szVzt0Ur4m2&grant_type=authorization_code
//https://api.weixin.qq.com/sns/userinfo?access_token=47_0ZFNUORANnbPyESYiFXUqANM3F21lZXzWSaWyxn2C4G7Jqk4weTO9ReOZO-RjP5u6PxUUCX2ZaAY6MaVrMXe9A&openid=o_G_X5xcn4wg10FF9rMJV2CorWY0&lang=zh_CN
