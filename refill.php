<?php

$api_key = getenv("SMMBIND_API_KEY");

$orders = [
    "217990724",
    "213514284"
];

echo "بدء الفحص...\n\n";

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
    curl_close($ch);

    echo "الأوردر: $order\n";
    echo "رد الموقع: $result\n";

    $txt = strtolower($result);

    if (
        strpos($txt, "success") !== false ||
        strpos($txt, "refill request has been sent") !== false
    ) {
        echo "النتيجة: تمت إعادة التعبئة بنجاح ✅\n";
    }
    elseif (
        strpos($txt, "available in") !== false ||
        strpos($txt, "hours") !== false ||
        strpos($txt, "minutes") !== false
    ) {
        echo "النتيجة: لسه باقي وقت على إعادة التعبئة ⏳\n";
    }
    else {
        echo "النتيجة: رد غير معروف\n";
    }

    echo "--------------------------\n";
}
