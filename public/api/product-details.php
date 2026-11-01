
<?php

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/app/config/database.php';

try {

    $productId = $_GET['id'] ?? '';

    if (!ctype_digit((string)$productId) || (int)$productId < 1) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Valid product ID is required'
        ]);

        exit;
    }

    // Fetch product details
    $sql = "
        SELECT
            p.id,
            p.category_id,
            c.name AS category_name,
            p.name,
            p.slug,
            p.image_path,
            p.short_description,
            p.description,
            p.min_price,
            p.max_price,
            p.customization_options,
            p.created_at
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.id = :id
        AND p.is_active = 1
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => (int)$productId
    ]);

    $product = $stmt->fetch();

    if (!$product) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);

        exit;
    }

    // Fetch associated occasions
    $occasionSql = "
        SELECT o.id, o.name, o.slug
        FROM occasions o
        INNER JOIN product_occasions po
            ON po.occasion_id = o.id
        WHERE po.product_id = :product_id
        ORDER BY o.name ASC
    ";

    $occasionStmt = $pdo->prepare($occasionSql);
    $occasionStmt->execute([
        ':product_id' => (int)$productId
    ]);

    $product['occasions'] = $occasionStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'message' => 'Product details fetched successfully',
        'data' => $product
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch product details'
    ]);
}
