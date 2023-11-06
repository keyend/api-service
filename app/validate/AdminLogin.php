<?php
namespace app\validate;
/**
 * 用户登录验证器
 * 
 * @package admin.validate
 * @author k.
 */
use mashroom\provider\Validate;

class AdminLogin extends Validate
{
    protected $rule = [
        'username|账号' => 'require|min:4|max:32',
        'password|密码' => 'require|min:6|max:16',
        'code|验证码' => 'require|min:4|max:512',
        'key|令牌' => 'require|min:2|max:64',
        'real_name|真实姓名' => 'max:25',
        'phone|手机号' => 'isPhone',
        'birthday|生日' => 'dateFormat:Y-m-d',
        'card_id|身份证' => 'length:18',
        'addres|用户地址' => 'max:64',
        'mark|备注' => 'max:200',
        'group_id|分组' => 'integer',
        'label_id|标签' => 'array',
        'is_promoter|推广人' => 'in:0,1'
    ];

    protected $scene = [
        'login'  =>  ['username','password','code','key'],
    ];
}
