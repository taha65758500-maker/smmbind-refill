<?php
$api_key = getenv("SMMBIND_API_KEY");
$orders = ["217990724", "213514284"];

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

    echo "الأوردر: " . $order . "\n";

    if (strpos($txt, "less than 24 hours ago") !== false) {
        echo "لسه باقي وقت على إعادة التعبئة ⏳\n";
    } elseif (strpos($txt, "error") === false) {
        echo "تمت إعادة التعبئة بنجاح ✅\n";
    } else {
        echo "رد الموقع: " . $result . "\n";
    }

    echo "-----------------\n";
}
?>
