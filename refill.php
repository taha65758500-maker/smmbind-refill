<?php

date_default_timezone_set("UTC");

$api_key = getenv("SMMBIND_API_KEY");

$telegram_token = getenv("TELEGRAM_BOT_TOKEN");
$telegram_chat_id = getenv("TELEGRAM_CHAT_ID");

$orders = [
    "217990724",
    "213514284"
];

$cooldown_hours = 2;

$state_file = "orders_state.json";

$lock_file = "script.lock";



/*
|--------------------------------------------------------------------------
| منع تشغيل الاسكريبت مرتين مع بعض
|--------------------------------------------------------------------------
*/

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
| تحميل الحالة القديمة
|--------------------------------------------------------------------------
*/

$state = [];

if (file_exists($state_file)) {

    $json = file_get_contents($state_file);

    $state = json_decode($json, true);

    if (!is_array($state)) {
        $state = [];
    }
}



/*
|--------------------------------------------------------------------------
| إنشاء حالة أولية لكل الأوردرات
|--------------------------------------------------------------------------
*/

$current_time = time();

foreach ($orders as $index => $order_id) {

    if (!isset($state[$order_id])) {

        $state[$order_id] = [
            "last_refill" => 0,

            // توزيع الأوردرات على ساعات مختلفة
            "next_refill" => $current_time + ($index * 3600)
        ];
    }
}



/*
|--------------------------------------------------------------------------
| اختيار الأوردر المستحق فقط
|--------------------------------------------------------------------------
*/

$order_to_process = null;

foreach ($orders as $order_id) {

    if ($current_time >= $state[$order_id]["next_refill"]) {

        $order_to_process = $order_id;

        break;
    }
}



/*
|--------------------------------------------------------------------------
| لا يوجد أوردر مستحق الآن
|--------------------------------------------------------------------------
*/

if (!$order_to_process) {

    echo "No refill needed now";

    sendTelegram("⏳ لا يوجد أي أوردر مستحق لإعادة التعبئة الآن");

    flock($lock, LOCK_UN);
    fclose($lock);

    exit;
}



/*
|--------------------------------------------------------------------------
| بدء العملية
|--------------------------------------------------------------------------
*/

sendTelegram("🚀 بدء إعادة التعبئة للأوردر {$order_to_process}");



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

    $state[$order_to_process]["last_refill"] = $current_time;

    $state[$order_to_process]["next_refill"] =
        $current_time + ($cooldown_hours * 3600);

    file_put_contents(
        $state_file,
        json_encode($state, JSON_PRETTY_PRINT)
    );

    $message =
        "✅ تمت إعادة التعبئة بنجاح\n\n" .
        "📦 الأوردر: {$order_to_process}\n" .
        "🆔 Refill ID: {$response["refill"]}\n" .
        "⏰ إعادة التعبئة القادمة بعد ساعتين";

    echo $message;

    sendTelegram($message);

} else {

    $state[$order_to_process]["next_refill"] =
        $current_time + 1800;

    file_put_contents(
        $state_file,
        json_encode($state, JSON_PRETTY_PRINT)
    );

    $message =
        "⚠️ فشل أو لم يسمح الموقع بإعادة التعبئة الآن\n\n" .
        "📦 الأوردر: {$order_to_process}\n" .
        "🔁 سيتم المحاولة بعد 30 دقيقة";

    echo $message;

    sendTelegram($message);
}



/*
|--------------------------------------------------------------------------
| إنهاء
|--------------------------------------------------------------------------
*/

sendTelegram("✅ انتهى فحص إعادة التعبئة");

flock($lock, LOCK_UN);

fclose($lock);

?>
