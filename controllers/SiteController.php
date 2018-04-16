<?php

namespace app\controllers;

require_once(__DIR__.'/../phpcas/CAS.php');

use Yii;
use yii\web\Response;
use yii\web\Controller;
use app\models\ContactForm;
use phpCAS;
use app\models\User;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\filters\CasFilter;

class SiteController extends Controller
{
    public $layout = 'main';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'cas' => [
                'class' => CasFilter::className(),
                'except' => ['index'],
            ],
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
     * 登录流程，先跳转到cas-server,
     * 登录成功后再跳转回来进行一次login，保留了之前的rbac流程不变
     */
    public function actionLogin(){
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
       // Yii::$app->response->redirect('/site/index' , 301)->send();
        return $this->redirect('/site/index');
    }


    /**
     * Logout action.
     * @return bool
     */
    public function actionLogout()
    {
        $cas_server_url = Yii::$app->params['cas']['cas_host'].':'.Yii::$app->params['cas']['cas_port'].Yii::$app->params['cas']['cas_context'].'/logout?embed=true';
        $own_server_url = Yii::$app->request->getHostInfo();
        phpCAS::setServerLogoutURL('http://'.$cas_server_url.'&service='.$own_server_url.'/');

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
