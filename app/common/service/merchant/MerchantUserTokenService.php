<?php


namespace app\common\service\merchant;

use app\common\service\merchant\MerchantUserService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use app\common\service\system\SettingService;
use app\common\service\utils\RetCodeUtils;

/**
 * 商家用户Token
 */
class MerchantUserTokenService
{
    /**
     * Token配置
     *
     * @return array
     */
    public static function config()
    {
        $config = SettingService::info();
        $config['token_alg'] = 'HS256';
        return $config;
    }

    /**
     * Token生成
     * 
     * @param array $user 用户信息
     * 
     * @return string
     */
    public static function create($user)
    {
        $config = self::config();

        $payload = [
            'iat'  => time(),                           //签发时间
            'nbf'  => time(),                           //生效时间
            'exp'  => time() + $config['token_exps'],   //过期时间
            'data' => [
                'mer_user_id'    => $user['mer_user_id'],
                'mer_id'    => $user['mer_id'],
                'login_time' => $user['login_time'],
            ],
        ];

        return JWT::encode($payload, $config['token_key'], $config['token_alg']);
    }

    /**
     * Token decode
     *
     * @param  string $token
     * @param  bool   $exce 是否抛出异常
     * 
     * @return object|Exception
     */
    public static function decode($token, $exce = true)
    {
        try {
            $config = self::config();
            $decode = JWT::decode($token, new Key($config['token_key'], $config['token_alg']));
        } catch (\Exception $e) {
            if ($exce) {
                exception('登录状态已失效', RetCodeUtils::LOGIN_INVALID);
            }
        }

        return $decode ?? [];
    }

    /**
     * Token验证
     *
     * @param string $token token
     * 
     * @return void|Exception
     */
    public static function verify($token)
    {
        if (empty($token)) {
            exception('请登录', RetCodeUtils::LOGIN_INVALID);
        }

        try {
            $decode  = self::decode($token);
            $mer_user_id = $decode->data->mer_user_id;
        } catch (\Exception $e) {
            exception('账号登录状态已失效', RetCodeUtils::LOGIN_INVALID);
        }

        $user = MerchantUserService::info($mer_user_id);
        if ($user['is_delete'] == 1) {
            exception('账号已被删除', RetCodeUtils::LOGIN_INVALID);
        }
        if ($user['is_disable'] == 1) {
            exception('账号已被禁用', RetCodeUtils::LOGIN_INVALID);
        }
        if (($user['pwd_time'] ?? '') && $decode->data->login_time < $user['pwd_time']) {
            exception('账号密码已修改', RetCodeUtils::LOGIN_INVALID);
        }

        $config = self::config();
        if ($config['is_multi_login'] == 0) {
            if ($decode->data->login_time != $user['login_time']) {
                exception('账号已在另一处登录', RetCodeUtils::LOGIN_INVALID);
            }
        }
    }

    /**
     * Token用户id
     *
     * @param string $token token
     * @param bool   $exce  是否抛出异常
     * 
     * @return int user_id
     */
    public static function merUserId($token, $exce = false)
    {
        $mer_user_id = 0;

        if ($token) {
            try {
                $decode  = self::decode($token);
                $mer_user_id = $decode->data->mer_user_id;
            } catch (\Exception $e) {
                $mer_user_id = 0;
            }
        }

        if (empty($mer_user_id) && $exce) {
            exception('请登录', RetCodeUtils::LOGIN_INVALID);
        }

        return $mer_user_id;
    }
    /**
     * Token商家id
     *
     * @param string $token token
     * @param bool   $exce  是否抛出异常
     *
     * @return int user_id
     */
    public static function merId($token, $exce = false)
    {
        $mer_id = 0;

        if ($token) {
            try {
                $decode  = self::decode($token);
                $mer_id = $decode->data->mer_id;
            } catch (\Exception $e) {
                $mer_id = 0;
            }
        }

        if (empty($mer_id) && $exce) {
            exception('请登录', RetCodeUtils::LOGIN_INVALID);
        }

        return $mer_id;
    }
}
