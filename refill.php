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

    $result = @file_get_contents($url, false, $context);

    if ($result === FALSE) {
        echo "Telegram Error\n";
    }
}

sendTelegram("🚀 بدأ فحص إعادة التعبئة الآن");

foreach ($orders as $order_id) {

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

        echo $message . "\n";

        sendTelegram($message);

    } else {

        $message = "⏳ الأوردر {$order_id}\nلسه باقي وقت على إعادة التعبئة";

        echo $message . "\n";

        sendTelegram($message);
    }

    sleep(5);
}

sendTelegram("✅ انتهى فحص إعادة التعبئة");
?>
