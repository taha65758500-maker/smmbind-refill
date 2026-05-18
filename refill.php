<?php

$api_key = getenv("SMMBIND_API_KEY");

$orders = [
    "217990724",
    "213514284"
];

$state_file = "last_order.txt";

$current_index = 0;

if (file_exists($state_file)) {
    $current_index = (int) file_get_contents($state_file);
}

$order_id = $orders[$current_index];

$next_index = ($current_index + 1) % count($orders);

file_put_contents($state_file, $next_index);

echo "🚀 بدء فحص إعادة التعبئة الآن\n\n";

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
    ],
];

$context = stream_context_create($options);

$result = file_get_contents($url, false, $context);

$response = json_decode($result, true);

echo "الأوردر {$order_id}\n";

if (isset($response["refill"])) {
    echo "✅ تمت إعادة التعبئة بنجاح\n";
} else {
    echo "⏳ لسه باقي وقت على إعادة التعبئة\n";
}

echo "\n✅ انتهى فحص إعادة التعبئة\n";
?>
