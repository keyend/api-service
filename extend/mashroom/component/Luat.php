<?php
namespace mashroom\component;
/**
 * 流量卡管理
 * 
 * Luat::getList 获取流量卡列表
 * Luat::getPackageList 获取套餐列表
 * Luat::recharge 充值
 * 
 * @date    2021-05-10 20:19:31
 * @version 1.0
 * @author  easyzf.cn
 */
class Luat
{
    //API地址
    const OAuthURI = "http://api.openluat.com";
    const OAuthAppID = "cUTPGFSYr55YBjMO";
    const OAuthAppSecert = "3vR0ABlyquyXhCyjQlmUDE9546cWPdMBBbDROohOEiTbPwPyEdwuXVSRSrh4hG2X";
    const UriAccount = "/sim/iotcard/account/fetch";
    const UriPolicyGroup = "/sim/iotcard/billing_group";
    const UriList = "/sim/iotcard/cardlist";
    const UriInfo = "/sim/iotcard/card";
	  const UriPayPolicy = "/sim/iotcard/product/fetch";
    const UriPayment = "/sim/iotcard/make_order";
    const parseError = "invalid result";

    /**
     * 获取卡片列表
     *
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function getList($limit=9999, $page=1) 
    {
        $params = array('bg_code' => $policyCode, 'page' => $page, 'psize' => $limit);
        $header = array(
            'Content-Type: application/json; charset=utf-8',
            'X-AjaxPro-Method:ShowList'
        );

        $params = json_encode($params);

        if (!$res = $this->post(self::OAuthURI . self::UriList, $params, $header)) {
            return false;
        }

        if (!isset($res['code'])) {
            $this->error = self::parseError;
            return false;
        }

        if (0 != $res['code']) {
            $this->error = $res['msg'];
            return false;
        }

        return $res;
    }

    public function getDetail($iccid)
    {

    }

    public function getPackageList()
    {}

    public function recharge()
    {}
}