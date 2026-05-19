<?php

date_default_timezone_set("UTC");

$api_key = getenv("SMMBIND_API_KEY");

$telegram_token = getenv("TELEGRAM_BOT_TOKEN");
$telegram_chat_id = getenv("TELEGRAM_CHAT_ID");



/*
|--------------------------------------------------------------------------
| الأوردرات
|--------------------------------------------------------------------------
*/

$orders = [
    "217990724",
    "213514284"
];



/*
|--------------------------------------------------------------------------
| منع تشغيل الاسكريبت مرتين مع بعض
|--------------------------------------------------------------------------
*/

$lock_file = "script.lock";

$lock = fopen($lock_file, "c");

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    exit("Script already running");
}



/*
|--------------------------------------------------------------------------
| Telegram
|--------------------------------------------------------------------------
*/

function sendTelegram($message)
{
    global $telegram_token, $telegram_chat_id;

    if (!$telegram_token || !$telegram_chat_id) {
        return;
    }

    $url = "https://api.telegram.org/bot{$telegram_token}/sendMessage";

    $data = [
        "chat_id" => $telegram_chat_id,
        "text" => $message
    ];

    $options = [
        "http" => [
            "header"  => "Content-type: application/x-www-form-urlencoded",
            "method"  => "POST",
            "content" => http_build_query($data),
            "timeout" => 20
        ]
    ];

    $context = stream_context_create($options);

    @file_get_contents($url, false, $context);
}



/*
|--------------------------------------------------------------------------
| نظام التبديل بين الأوردرات
|--------------------------------------------------------------------------
*/

$rotation_file = "rotation.txt";

$current_index = 0;

if (file_exists($rotation_file)) {

    $saved = file_get_contents($rotation_file);

    if (is_numeric($saved)) {
        $current_index = (int) $saved;
    }
}

if ($current_index >= count($orders)) {
    $current_index = 0;
}

$order_to_process = $orders[$current_index];

$next_index = ($current_index + 1) % count($orders);

file_put_contents($rotation_file, $next_index);



/*
|--------------------------------------------------------------------------
| بدء الفحص
|--------------------------------------------------------------------------
*/

sendTelegram("🚀 بدء فحص إعادة التعبئة للأوردر {$order_to_process}");



/*
|--------------------------------------------------------------------------
| إرسال طلب Refill
|--------------------------------------------------------------------------
*/

$url = "https://smmbind.com/api/v2";

$data = [
    "key" => $api_key,
    "action" => "refill",
    "order" => $order_to_process
];

$options = [
    "http" => [
        "header"  => "Content-type: application/x-www-form-urlencoded",
        "method"  => "POST",
        "content" => http_build_query($data),
        "timeout" => 30
    ]
];

$context = stream_context_create($options);

$result = @file_get_contents($url, false, $context);

$response = json_decode($result, true);



/*
|--------------------------------------------------------------------------
| نجاح إعادة التعبئة
|--------------------------------------------------------------------------
*/

if (isset($response["refill"])) {

    $message =
        "✅ تمت إعادة التعبئة بنجاح\n\n" .
        "📦 الأوردر: {$order_to_process}\n" .
        "🆔 Refill ID: {$response["refill"]}";

    echo $message;

    sendTelegram($message);

} else {

    $error_text = $result;

    if (isset($response["error"])) {
        $error_text = $response["error"];
    }

    $message =
        "⏳ لم يسمح الموقع بإعادة التعبئة الآن\n\n" .
        "📦 الأوردر: {$order_to_process}\n\n" .
        "🧾 رد الموقع:\n{$error_text}";

    echo $message;

    sendTelegram($message);
}



/*
|--------------------------------------------------------------------------
| انتهاء الفحص
|--------------------------------------------------------------------------
*/

sendTelegram("✅ انتهى فحص إعادة التعبئة");

flock($lock, LOCK_UN);

fclose($lock);

?>
