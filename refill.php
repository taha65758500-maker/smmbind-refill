<?php

$api_key = getenv("SMMBIND_API_KEY");
$telegram_token = getenv("TELEGRAM_BOT_TOKEN");
$telegram_chat_id = getenv("TELEGRAM_CHAT_ID");

$orders = [
    "217990724",
    "213514284"
];

function sendTelegram($message) {
    global $telegram_token, $telegram_chat_id;

    if (!$telegram_token || !$telegram_chat_id) {
        echo "Telegram data missing\n";
        return;
    }

    $url = "https://api.telegram.org/bot" . $telegram_token . "/sendMessage";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        "chat_id" => $telegram_chat_id,
        "text" => $message
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

echo "بدء الفحص...\n\n";

sendTelegram("🚀 بدأ فحص إعادة التعبئة الآن");

foreach ($orders as $order) {

    $ch = curl_init("https://smmbind.com/api/v2");

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        "key" => $api_key,
        "action" => "refill",
        "order" => $order
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);

        $message = "❌ خطأ في الأوردر: $order\n$error";
        echo $message . "\n";
        sendTelegram($message);
        continue;
    }

    curl_close($ch);

    $txt = strtolower($result);

    echo "الأوردر: $order\n";

    if (strpos($txt, "less than 24 hours ago") !== false) {
        $message = "⏳ الأوردر $order\nلسه باقي وقت على إعادة التعبئة";
        echo $message . "\n";
        sendTelegram($message);
    }
    elseif (strpos($txt, "error") === false) {
        $message = "✅ الأوردر $order\nتمت إعادة التعبئة بنجاح";
        echo $message . "\n";
        sendTelegram($message);
    }
    else {
        $message = "❌ الأوردر $order\nفشلت إعادة التعبئة\nرد الموقع: $result";
        echo $message . "\n";
        sendTelegram($message);
    }

    echo "-----------------\n";
}

sendTelegram("✅ انتهى فحص إعادة التعبئة");
?>
