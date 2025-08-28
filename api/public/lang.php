<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$lang = $_GET['code'] ?? 'en';
$langFile = "../../lang/{$lang}.json";

if (file_exists($langFile)) {
    $content = file_get_contents($langFile);
    echo $content;
} else {
    // Fallback to English
    $fallbackFile = "../../lang/en.json";
    if (file_exists($fallbackFile)) {
        $content = file_get_contents($fallbackFile);
        echo $content;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Language file not found']);
    }
}
?>
