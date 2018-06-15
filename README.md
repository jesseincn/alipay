# Alipay
支付宝 OAuth2 账号连接 for Laravel 5.x.

## Installation

1. 安装 composer package:

		composer require ikerlin/alipay

2. config/app.php 中 `Laravel\Socialite\SocialiteServiceProvider` 替换成 `SocialiteProviders\Manager\ServiceProvider`

3. `app/Providers/EventServiceProvider.php` 中添加一个监听器：`SocialiteProviders\Manager\SocialiteWasCalled`，如果已存在则忽略；并添加监听响应事件：`Ikerlin\Alipay\AlipayExtendSocialite@handle`

    ```
    'SocialiteProviders\Manager\SocialiteWasCalled' => [
        // ...
        'Ikerlin\Alipay\AlipayExtendSocialite@handle',
    ],
    ```

4. config/service.php 中添加一个配置项：

    ```
    'alipay' => [
        'client_id'     => env('ALIPAY_KEY'),
        'client_secret' => env('ALIPAY_SECRET'), //未使用参数，但不可移除
        'privateKeyFilePath' => env('ALIPAY_PRIVATE_KEY_FILE_PATH'), //相对于storage目录
        'publicKeyFilePath' => env('ALIPAY_PUBLIC_KEY_FILE_PATH'), //相对于storage目录
        'redirect'      => env('ALIPAY_REDIRECT_URL'),
    ],
    ```

## Usage

详见官方文档 [socialite](http://laravel.com/docs/5.5/authentication#social-authentication) 用法。

[SocialiteProviders](https://github.com/SocialiteProviders)


## License

MIT License.
