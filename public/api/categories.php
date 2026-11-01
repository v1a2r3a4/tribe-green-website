
<?php

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/app/config/database.php';

try {

    $sql = "SELECT id, name, slug
            FROM categories
            ORDER BY name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $categories = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'message' => 'Categories fetched successfully',
        'data' => $categories
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch categories'
    ]);
}
