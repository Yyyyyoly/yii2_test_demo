<?php

namespace app\controllers;

// Load the CAS lib
require_once __DIR__ . '/../vendor/jasig/phpcas/CAS.php';


use Yii;
use yii\web\Controller;
use phpCAS;

class BaseController extends Controller
{

    public function __construct()
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
        $cas_server_url = Yii::$app->params['cas']['cas_host'].':'.Yii::$app->params['cas']['cas_port'].Yii::$app->params['cas']['cas_context'].'/login?embed=true';
        $own_server_url = Yii::$app->request->getHostInfo().Yii::$app->request->url;
        phpCAS::setServerLoginUrl('http://'.$cas_server_url.'&service='.$own_server_url);

        // For production use set the CA certificate that is the issuer of the cert
        // on the CAS server and uncomment the line below
        // phpCAS::setCasServerCACert($cas_server_ca_cert_path);

        // For quick testing you can disable SSL validation of the CAS server.
        // THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
        // VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
        phpCAS::setNoCasServerValidation();

        //这里会检测服务器端的退出的通知，就能实现php和其他语言平台间同步登出了
        phpCAS::handleLogoutRequests();

        // force CAS authentication
        if (phpCAS::checkAuthentication()) {
            //获取登陆的用户名
            $username = phpCAS::getUser();
            Yii::info($username, __METHOD__);
        } else {
            // 访问CAS的验证
            phpCAS::forceAuthentication();
            exit;
        }
    }
}
