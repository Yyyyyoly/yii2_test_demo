<?php
/**
 * Created by PhpStorm.
 * User: wangyg
 * Date: 2018/4/16
 * Time: 14:08
 */

namespace app\filters;

require_once(__DIR__ . '/../phpcas/CAS.php');

use Yii;
use yii\base\ActionFilter;
use phpCAS;

class CasFilter extends ActionFilter
{
    public function beforeAction($action)
    {
        // Initialize phpCAS
        phpCAS::client(
            CAS_VERSION_2_0,
            Yii::$app->params['cas']['cas_host'],
            Yii::$app->params['cas']['cas_port'],
            Yii::$app->params['cas']['cas_context'],
            true
        );

        //登陆成功后跳转的地址 -- 登陆方法中加此句
        $cas_server_url = Yii::$app->params['cas']['cas_host'] . ':' . Yii::$app->params['cas']['cas_port'] . Yii::$app->params['cas']['cas_context'] . '/login?embed=true';
        $own_server_url = Yii::$app->request->getHostInfo() . '/site/login';
        phpCAS::setServerLoginUrl('http://' . $cas_server_url . '&service=' . $own_server_url);

        // automatically log the user when there is a cas session opened
        // phpCAS::setCacheTimesForAuthRecheck(-1);

        // For production use set the CA certificate that is the issuer of the cert
        // on the CAS server and uncomment the line below
        if (Yii::$app->params['cas']['ca_cert_way'] == 'https') {
            phpCAS::setCasServerCACert(Yii::$app->params['cas']['cas_server_ca_cert_path']);
        } else {
            // For quick testing you can disable SSL validation of the CAS server.
            // THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
            // VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
            phpCAS::setNoCasServerValidation();
        }

        // 其他服务器退出时是否通知（如果同时还有java 等其他语言的client）
        if (Yii::$app->params['cas']['log_out_request'] == false) {
            phpCAS::handleLogoutRequests(false);
        } else {
            phpCAS::handleLogoutRequests(true);
        }
        // force CAS authentication
        if (phpCAS::checkAuthentication()) {
            return parent::beforeAction($action);
        } else {
            // 访问CAS的验证
            phpCAS::forceAuthentication();
        }
    }
}