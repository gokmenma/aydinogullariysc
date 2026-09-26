<?php 

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

echo json_encode([
    'status' => 200,
    'message' => 'Logger test successful'
]);

?>
