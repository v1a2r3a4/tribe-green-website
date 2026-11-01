
<?php

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/app/config/database.php';

// Accept POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed'
    ]);

    exit;
}

try {

    // Read JSON request from Flutter
    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid JSON request');
    }

    // Customer details
    $fullName = trim($input['full_name'] ?? '');
    $companyName = trim($input['company_name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $email = trim($input['email'] ?? '');

    $occasionId = $input['occasion_id'] ?? null;
    $quantity = filter_var(
        $input['quantity'] ?? null,
        FILTER_VALIDATE_INT
    );

    $budgetRange = trim($input['budget_range'] ?? '');
    $deliveryDate = trim($input['delivery_date'] ?? '');
    $customizationDetails = trim(
        $input['customization_details'] ?? ''
    );
    $message = trim($input['message'] ?? '');

    $productIds = $input['product_ids'] ?? [];

    // Validate required fields
    if ($fullName === '' || $phone === '' || $email === '') {
        throw new InvalidArgumentException(
            'Name, phone and email are required'
        );
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email address');
    }

    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        throw new InvalidArgumentException('Invalid phone number');
    }

    if ($quantity === false || $quantity < 1 || $quantity > 100000) {
        throw new InvalidArgumentException('Invalid quantity');
    }

    if (!is_array($productIds) || count($productIds) < 1) {
        throw new InvalidArgumentException(
            'Please select at least one product'
        );
    }

    if (count($productIds) > 50) {
        throw new InvalidArgumentException(
            'You can select up to 50 products'
        );
    }

    // Validate product IDs
    $productIds = array_values(array_unique($productIds));

    foreach ($productIds as $id) {
        if (
            filter_var($id, FILTER_VALIDATE_INT) === false ||
            (int)$id < 1
        ) {
            throw new InvalidArgumentException(
                'Invalid product selection'
            );
        }
    }

    $productIds = array_map('intval', $productIds);

    // Validate occasion if provided
    if ($occasionId !== null && $occasionId !== '') {

        if (
            filter_var($occasionId, FILTER_VALIDATE_INT) === false ||
            (int)$occasionId < 1
        ) {
            throw new InvalidArgumentException('Invalid occasion');
        }

        $occasionId = (int)$occasionId;

        $occasionStmt = $pdo->prepare(
            "SELECT id FROM occasions WHERE id = :id"
        );

        $occasionStmt->execute([':id' => $occasionId]);

        if (!$occasionStmt->fetch()) {
            throw new InvalidArgumentException('Occasion not found');
        }

    } else {
        $occasionId = null;
    }

    // Validate delivery date if provided
    if ($deliveryDate !== '') {
        $date = DateTime::createFromFormat('Y-m-d', $deliveryDate);

        if (!$date || $date->format('Y-m-d') !== $deliveryDate) {
            throw new InvalidArgumentException(
                'Delivery date must be in YYYY-MM-DD format'
            );
        }
    } else {
        $deliveryDate = null;
    }

    // Verify that all selected products exist and are active
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $productStmt = $pdo->prepare(
        "SELECT id FROM products
         WHERE is_active = 1 AND id IN ($placeholders)"
    );

    $productStmt->execute($productIds);

    $validProducts = $productStmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($validProducts) !== count($productIds)) {
        throw new InvalidArgumentException(
            'One or more selected products are unavailable'
        );
    }

    // Generate enquiry reference
    $referenceNo = 'TG-' . date('Ymd') . '-' .
        strtoupper(bin2hex(random_bytes(4)));

    // Save enquiry and selected products together
    $pdo->beginTransaction();

    $sql = "
        INSERT INTO enquiries (
            reference_no,
            full_name,
            company_name,
            phone,
            email,
            occasion_id,
            quantity,
            budget_range,
            delivery_date,
            customization_details,
            message,
            source,
            status
        ) VALUES (
            :reference_no,
            :full_name,
            :company_name,
            :phone,
            :email,
            :occasion_id,
            :quantity,
            :budget_range,
            :delivery_date,
            :customization_details,
            :message,
            'android_app',
            'New'
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':reference_no' => $referenceNo,
        ':full_name' => $fullName,
        ':company_name' => $companyName ?: null,
        ':phone' => $phone,
        ':email' => $email,
        ':occasion_id' => $occasionId,
        ':quantity' => $quantity,
        ':budget_range' => $budgetRange ?: null,
        ':delivery_date' => $deliveryDate,
        ':customization_details' => $customizationDetails ?: null,
        ':message' => $message ?: null
    ]);

    $enquiryId = (int)$pdo->lastInsertId();

    // Save selected products
    $linkStmt = $pdo->prepare(
        "INSERT INTO enquiry_products (enquiry_id, product_id)
         VALUES (:enquiry_id, :product_id)"
    );

    foreach ($productIds as $productId) {
        $linkStmt->execute([
            ':enquiry_id' => $enquiryId,
            ':product_id' => $productId
        ]);
    }

    $pdo->commit();

    http_response_code(201);

    echo json_encode([
        'success' => true,
        'message' => 'Enquiry submitted successfully',
        'data' => [
            'enquiry_id' => $enquiryId,
            'reference_no' => $referenceNo,
            'source' => 'android_app',
            'status' => 'New'
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (InvalidArgumentException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to submit enquiry. Please try again.'
    ]);
}
