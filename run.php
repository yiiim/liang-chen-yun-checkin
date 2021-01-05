<?php
/**
 * Created by IntelliJ IDEA.
 * User: hugh.li
 * Date: 2019/12/12
 * Time: 15:48
 */

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Symfony\Component\Process\Process;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

$email = $argv[1];
$password = $argv[2];

/** @var integer $repeatCount 记录重试次数 */
$repeatCount = 0;


$isOk = false;

$url = 'https://良辰美景.icu/';
$host = 'http://localhost:4444/wd/hub';

/** 设置代理调试的时候使用 */
//$capabilities->setCapability(WebDriverCapabilityType::PROXY, ['proxyType' => 'system', 'httpProxy' => 'localhost:8888']);

$options = new ChromeOptions();
#$options->addArguments(['--headless', '--no-sandbox']);

$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
$driver = RemoteWebDriver::create($host, $capabilities, (120 * 1000), (120 * 1000));

$driver->get($url);

/** 浏览器最大化 */
$driver->manage()->window()->maximize();

/** 等待页面跳转成功 */
$class = WebDriverBy::className('content');
$condition = WebDriverExpectedCondition::visibilityOfElementLocated($class);
$driver->wait(10)->until($condition);

/** 跳登入 */
$buttonElement = $driver->findElement(WebDriverBy::id('wrapper'))
    ->findElement(WebDriverBy::linkText('登录'));
$driver->executeScript("arguments[0].click();", [$buttonElement]);


/** 邮箱和密码的输入框 */
$emailInput = WebDriverBy::cssSelector('input[name="Email"]');
$passwordInput = WebDriverBy::cssSelector('input[name="Password"]');

/** 等待 邮箱输入框 被渲染出来 */
$driver->wait(30)->until(
    WebDriverExpectedCondition::visibilityOfElementLocated($emailInput)
);

/** 输入 邮箱和密码 */
$driver->findElement($emailInput)->sendKeys($email);
$driver->findElement($passwordInput)->sendKeys($password);

/** 点击登入 */
$loginButton = WebDriverBy::cssSelector('button[id="login"]');
$loginButtonElement = $driver->findElement($loginButton);
$driver->executeScript("arguments[0].click();", [$loginButtonElement]);

Checkin:

/** 最多重试5次 */
if (5 <= $repeatCount++) {
    goto Result;
}

/** 确认按钮 */
try {
    sleep(30);
    $resultOk = WebDriverBy::cssSelector('#result_ok');
    $resultOkElement = $driver->findElement($resultOk);
    $driver->executeScript("arguments[0].click();", [$resultOkElement]);
} catch (NoSuchElementException $exception) {
}

/** 等待 首页 被渲染出来 */
sleep(5);
$remain = WebDriverBy::cssSelector('#remain');
echo date('Y-m-d H:i:s') . "  剩余流量" . $driver->findElement($remain)->getText(), PHP_EOL;


/** 上次签到时间 */
try {
    $message = WebDriverBy::cssSelector('body > main > div.container > section > div:nth-child(2) > div.col-xx-12.col-sm-5 > div:nth-child(1) > div > div:nth-child(2) > p:nth-child(2)');
    echo date('Y-m-d H:i:s') . ' ' . ($text = $driver->findElement($message)->getText()), PHP_EOL;
} catch (NoSuchElementException $exception) {
}

/** 判断今天是否已经签到 */
try {
    echo date('Y-m-d H:i:s') . " 第{$repeatCount}次检查", PHP_EOL;

    $message = WebDriverBy::cssSelector('a.btn.btn-brand.disabled.btn-flat');
    echo date('Y-m-d H:i:s') . " 今天签到信息: " . ($text = $driver->findElement($message)->getText()), PHP_EOL;
    if (($isOk = false !== strpos($text, "今日已签到"))) {
        goto Result;
    }
} catch (NoSuchElementException $exception) {
}

/** 点击签到 */
try {
    $checkinButton = WebDriverBy::cssSelector('button[id="checkin"]');
    $checkinButtonElement = $driver->findElement($checkinButton);
    $driver->executeScript("arguments[0].click();", [$checkinButtonElement]);

    sleep(10);

    /** 因为经常获取不到签到后的信息, 所有直接刷新页面去处理 */
    echo date('Y-m-d H:i:s') . " 刷新页面去检查签到结果......", PHP_EOL;
    $driver->executeScript("location.reload();");
    goto Checkin;

} catch (NoSuchElementException $exception) {
}

Result:

$driver->close();
$driver->quit();
isset($process) && $process->isRunning() && $process->stop(100);

if (!$isOk) {
    throw new Exception('签到失败');
}
