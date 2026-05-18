<?php

$api_key = getenv("SMMBIND_API_KEY");

$telegram_token = getenv("TELEGRAM_BOT_TOKEN");
$telegram_chat_id = getenv("TELEGRAM_CHAT_ID");

$orders = [
    "217990724",
    "213514284"
];

function sendTelegram($message)
{
    global $telegram_token, $telegram_chat_id;

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

$state_file = "last_order.txt";

$current_index = 0;

if (file_exists($state_file)) {
    $current_index = (int) file_get_contents($state_file);
}

$order_id = $orders[$current_index];

$next_index = ($current_index + 1) % count($orders);

file_put_contents($state_file, $next_index);

sendTelegram("🚀 بدأ فحص إعادة التعبئة الآن");

$url = "https://smmbind.com/api/v2";

$data = [
    "key" => $api_key,
    "action" => "refill",
    "order" => $order_id
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

$result = @file_get_contents($url, false, $context);

$response = json_decode($result, true);

if (isset($response["refill"])) {

    $message = "✅ الأوردر {$order_id}\nتمت إعادة التعبئة بنجاح";

} else {

    $message = "⏳ الأوردر {$order_id}\nلسه باقي وقت على إعادة التعبئة";
}

echo $message;

sendTelegram($message);

sendTelegram("✅ انتهى فحص إعادة التعبئة");

?>
