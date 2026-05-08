<?php

$api_key = getenv("SMMBIND_API_KEY");

if (!$api_key) {
    die("API KEY غير موجود\n");
}

$orders = [
    "217990724",
    "213514284"
];

echo "بدء فحص إعادة التعبئة...\n";

foreach ($orders as $order) {
    $data = [
        "key" => $api_key,
        "action" => "refill",
        "order" => $order
    ];

    $ch = curl_init("https://smmbind.com/api/v2");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $result = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "الأوردر {$order}: حصل خطأ -> " . curl_error($ch) . "\n";
        curl_close($ch);
        continue;
    }

    curl_close($ch);

    $text = strtolower($result);

    echo "الأوردر {$order}: ";

    if (
        str_contains($text, "success") ||
        str_contains($text, "refill") && !str_contains($text, "available")
    ) {
        echo "تمت إعادة التعبئة بنجاح ✅\n";
    } elseif (
        str_contains($text, "available") ||
        str_contains($text, "hours") ||
        str_contains($text, "minutes")
    ) {
        echo "لسه باقي وقت على إعادة التعبئة ⏳\n";
        echo "رد الموقع: {$result}\n";
    } else {
        echo "رد غير معروف من الموقع: {$result}\n";
    }

    sleep(2);
}

echo "انتهى الفحص\n";
?>
