<?php

$api_key = getenv("SMMBIND_API_KEY");

if (!$api_key) {
    die("API KEY غير موجود");
}

$orders = [
    "217990724",
    "213514284"
];

echo "بدء إعادة التعبئة...\n";

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
        echo "الأوردر {$order}: خطأ -> " . curl_error($ch) . "\n";
    } else {
        echo "الأوردر {$order}: {$result}\n";
    }

    curl_close($ch);

    sleep(2);
}

echo "تمت العملية\n";
?>
