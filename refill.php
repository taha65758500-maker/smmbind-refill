<?php

$api_key = getenv("SMMBIND_API_KEY");

$orders = [
    "217990724",
    "213514284"
];

$state_file = "last_order.txt";

$last_index = -1;

if (file_exists($state_file)) {
    $last_index = (int) file_get_contents($state_file);
}

$next_index = ($last_index + 1) % count($orders);

file_put_contents($state_file, $next_index);

$order_id = $orders[$next_index];

function sendTelegram($message) {
    $botToken = getenv("TELEGRAM_BOT_TOKEN");
    $chatId = getenv("TELEGRAM_CHAT_ID");

    file_get_contents(
        "https://api.telegram.org/bot$botToken/sendMessage?" .
        http_build_query([
            "chat_id" => $chatId,
            "text" => $message
        ])
    );
}

sendTelegram("🚀 بدء فحص إعادة التعبئة");

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
    ]
];

$context = stream_context_create($options);

$result = file_get_contents($url, false, $context);

$response = json_decode($result, true);

if (isset($response["refill"])) {

    sendTelegram("✅ الأوردر $order_id\nتمت إعادة التعبئة بنجاح");

} else {

    sendTelegram("⏳ الأوردر $order_id\nلسه باقي وقت على إعادة التعبئة");

}

sendTelegram("✅ انتهى فحص إعادة التعبئة");

?>
