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

    $txt = strtolower($result);

    echo "الأوردر: $order\n";

    if (strpos($txt, '"refill"') !== false || strpos($txt, 'success') !== false) {
        echo "تمت إعادة التعبئة بنجاح ✅\n";
    }
    elseif (strpos($txt, 'less than 24 hours ago') !== false) {
        echo "لسه باقي وقت على إعادة التعبئة ⏳\n";
    }
    else {
        echo "حالة غير معروفة\n";
    }

    echo "-----------------\n";
}
?>
