<?php
/**
 * 测试类
 * 
 * @version 1.0.0
 */
namespace app\controller\api\v2;
use app\controller\api\ApiBase;
use app\model\protocol\Protocol;
use app\model\protocol\Apps;
use app\model\member\Member;

class Test extends ApiBase 
{
    /**
     * 展示实现测试
     *
     * @param Protocol $protocol_model
     * @param Apps $apps_model
     * @return void
     */
    public function index(Protocol $protocol_model, Apps $apps_model, Member $member_model)
    {
        $member = $member_model->where([['is_virtual', '=', 1]])->orderRand()->find();
        if (!empty($member)) {
            $app = $apps_model->where([['member_id', '=', $member->member_id]])->find();
            $this->assign('app', $app);
        }

        $protocol_list = $protocol_model->order('id DESC')->select();
        foreach($protocol_list as $protocol) {
            $para = [
                'mer_id' => $app->id ?? 0,
                'param1' => '1234',
                'param2' => 'abc'
            ];
            $para['sign'] = !empty($app) ? $app->sign($para) : '';
            $protocol->para = $para;
        }

        $this->assign('protocol_list', $protocol_list);

        return $this->fetch();
    }
}