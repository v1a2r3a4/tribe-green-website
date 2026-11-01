
<?php

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/app/config/database.php';

try {

    // Read query parameters
    $search = trim($_GET['search'] ?? '');
    $categoryId = $_GET['category_id'] ?? null;
    $occasionId = $_GET['occasion_id'] ?? null;
    $minBudget = $_GET['min_budget'] ?? null;
    $maxBudget = $_GET['max_budget'] ?? null;

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 8)));

    $offset = ($page - 1) * $limit;

    // Build SQL conditions
    $conditions = ['p.is_active = 1'];
    $params = [];

    // Search filter
    if ($search !== '') {
        $conditions[] = '(
            p.name LIKE :search_name
            OR p.short_description LIKE :search_description
            OR p.description LIKE :search_description_text
        )';

        $searchValue = '%' . $search . '%';

        $params['search_name'] = $searchValue;
        $params['search_description'] = $searchValue;
        $params['search_description_text'] = $searchValue;
    }

    // Category filter
    if ($categoryId !== null && $categoryId !== '') {

        if (!ctype_digit((string)$categoryId) || (int)$categoryId < 1) {
            throw new InvalidArgumentException('Invalid category ID');
        }

        $conditions[] = 'p.category_id = :category_id';
        $params['category_id'] = (int)$categoryId;
    }

    // Occasion filter
    if ($occasionId !== null && $occasionId !== '') {

        if (!ctype_digit((string)$occasionId) || (int)$occasionId < 1) {
            throw new InvalidArgumentException('Invalid occasion ID');
        }

        $conditions[] = 'EXISTS (
            SELECT 1
            FROM product_occasions po
            WHERE po.product_id = p.id
            AND po.occasion_id = :occasion_id
        )';

        $params['occasion_id'] = (int)$occasionId;
    }

    // Minimum budget filter
    if ($minBudget !== null && $minBudget !== '') {

        if (!is_numeric($minBudget) || (float)$minBudget < 0) {
            throw new InvalidArgumentException('Invalid minimum budget');
        }

        $conditions[] = 'p.max_price >= :min_budget';
        $params['min_budget'] = (float)$minBudget;
    }

    // Maximum budget filter
    if ($maxBudget !== null && $maxBudget !== '') {

        if (!is_numeric($maxBudget) || (float)$maxBudget < 0) {
            throw new InvalidArgumentException('Invalid maximum budget');
        }

        $conditions[] = 'p.min_price <= :max_budget';
        $params['max_budget'] = (float)$maxBudget;
    }

    $where = implode(' AND ', $conditions);

    // Count matching products
    $countSql = "
        SELECT COUNT(*)
        FROM products p
        WHERE $where
    ";

    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);

    $totalProducts = (int)$countStmt->fetchColumn();

    // Fetch products
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
        WHERE $where
        ORDER BY p.created_at DESC, p.id DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {

        if (is_int($value)) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }
    }

    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

    $products = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'message' => 'Products fetched successfully',
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_products' => $totalProducts,
            'total_pages' => (int)ceil($totalProducts / $limit)
        ],
        'data' => $products
    ], JSON_UNESCAPED_UNICODE);

} catch (InvalidArgumentException $e) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} catch (Throwable $e) {

    error_log('Products API Error: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch products'
    ]);
}
