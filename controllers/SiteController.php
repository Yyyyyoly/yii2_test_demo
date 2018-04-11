<?php

namespace app\controllers;

// Load the CAS lib
require_once __DIR__ . '/../vendor/jasig/phpcas/CAS.php';


use Yii;
use yii\web\Response;
use yii\base\Controller;
use app\models\ContactForm;
use phpCAS;
use app\models\User;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class SiteController extends Controller
{
    public $layout = 'main';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('home', ['name' => '首页', 'message' => '这里是测试的首页']);
    }


    public function actionLogin(){
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
        $own_server_url = Yii::$app->request->getHostInfo().'/'.Yii::$app->request->pathInfo;
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
            $userInfo = Yii::$app->user->identity;
            if(is_object($userInfo) && $userInfo['username'] ==  $username){
                Yii::$app->user->login($userInfo);
            }
            else{
                $userInfo = User::findByUsername($username);
                // 该用户是否是第一次进入系统,是的话帮他注册一下
                if(!$userInfo || !$userInfo->getId()){
                    $new_user = new User();
                    $new_user->username = $username;
                    $new_user->email = $username.'@wangyaoguang.com';
                    $new_user->status = User::STATUS_ACTIVE;
                    $new_user->save();
                    Yii::$app->user->login($new_user);
                }
                else{
                    Yii::$app->user->login($userInfo);
                }
            }

            // 登录成功后跳转首页
            Yii::$app->response->redirect(['/site/index']);
        } else {
            // 访问CAS的验证
            phpCAS::forceAuthentication();
        }
    }


    /**
     * Logout action.
     * @return bool
     */
    public function actionLogout()
    {
        phpCAS::client(
            CAS_VERSION_2_0,
            Yii::$app->params['cas']['cas_host'],
            Yii::$app->params['cas']['cas_port'],
            Yii::$app->params['cas']['cas_context']
        );

        $cas_server_url = Yii::$app->params['cas']['cas_host'].':'.Yii::$app->params['cas']['cas_port'].Yii::$app->params['cas']['cas_context'].'/logout?embed=true';
        $own_server_url = Yii::$app->request->getHostInfo();
        phpCAS::setServerLogoutURL('http://'.$cas_server_url.'&service='.$own_server_url.'/');

        //no SSL validation for the CAS server
        phpCAS::setNoCasServerValidation();

        phpCAS::logout();

        Yii::$app->user->logout();
        return true;
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
