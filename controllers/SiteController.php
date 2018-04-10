<?php

namespace app\controllers;

// Load the CAS lib
require_once __DIR__ . '/../vendor/jasig/phpcas/CAS.php';


use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\ContactForm;
use phpCAS;

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

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!phpCAS::getUser()) {
            return $this->goHome();
        }

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
        $own_server_url = Yii::$app->request->getHostInfo().Yii::$app->request->getPathInfo();
        phpCAS::setServerLoginUrl('http://'.$cas_server_url.'&'.$own_server_url);

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
            $cas_user_id = phpCAS::getAttribute('id');
            Yii::info($cas_user_id.'_'.$username, __METHOD__);
            //用户登陆成功后,
            return true;
        } else {
            // 访问CAS的验证
            phpCAS::forceAuthentication();
            return false;
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
        $own_home_url = Yii::$app->getHomeUrl();
        phpCAS::setServerLoginUrl('http://'.$cas_server_url.'&'.$own_home_url);
        //no SSL validation for the CAS server
        phpCAS::setNoCasServerValidation();
        phpCAS::logout();
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
