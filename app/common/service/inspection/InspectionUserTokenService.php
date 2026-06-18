<?php


namespace app\common\service\inspection;

use app\common\service\inspection\InspectionUserService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use app\common\service\system\SettingService;
use app\common\service\utils\RetCodeUtils;

/**
 * 检测机构用户Token
 */
class InspectionUserTokenService
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
                'ins_user_id'    => $user['ins_user_id'],
                'ins_id'    => $user['ins_id'],
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
            $ins_user_id = $decode->data->ins_user_id;
        } catch (\Exception $e) {
            exception('账号登录状态已失效', RetCodeUtils::LOGIN_INVALID);
        }

        $user = InspectionUserService::info($ins_user_id);
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
    public static function insUserId($token, $exce = false)
    {
        $ins_user_id = 0;

        if ($token) {
            try {
                $decode  = self::decode($token);
                $ins_user_id = $decode->data->ins_user_id;
            } catch (\Exception $e) {
                $ins_user_id = 0;
            }
        }

        if (empty($ins_user_id) && $exce) {
            exception('请登录', RetCodeUtils::LOGIN_INVALID);
        }

        return $ins_user_id;
    }
    /**
     * Token检测机构id
     *
     * @param string $token token
     * @param bool   $exce  是否抛出异常
     *
     * @return int user_id
     */
    public static function insId($token, $exce = false)
    {
        $ins_id = 0;

        if ($token) {
            try {
                $decode  = self::decode($token);
                $ins_id = $decode->data->ins_id;
            } catch (\Exception $e) {
                $ins_id = 0;
            }
        }

        if (empty($ins_id) && $exce) {
            exception('请登录', RetCodeUtils::LOGIN_INVALID);
        }

        return $ins_id;
    }
}
