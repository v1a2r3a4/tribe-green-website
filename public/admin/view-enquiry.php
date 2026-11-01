
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../app/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Invalid enquiry ID.');
}

$allowedStatuses = ['New', 'Contacted', 'Converted', 'Lost'];
$error = '';
$success = isset($_GET['updated']) && $_GET['updated'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $newStatus = $_POST['status'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['admin_csrf'], $token)
    ) {
        $error = 'Your session token is invalid. Refresh the page and try again.';
    } elseif (!in_array($newStatus, $allowedStatuses, true)) {
        $error = 'Please select a valid status.';
    } else {
        $update = $pdo->prepare(
            'UPDATE enquiries SET status = :status WHERE id = :id'
        );
        $update->execute([
            'status' => $newStatus,
            'id' => $id,
        ]);

        header('Location: view-enquiry.php?id=' . $id . '&updated=1');
        exit;
    }
}

$stmt = $pdo->prepare(
    'SELECT
        e.*,
        o.name AS occasion_name
     FROM enquiries e
     LEFT JOIN occasions o ON o.id = e.occasion_id
     WHERE e.id = :id
     LIMIT 1'
);
$stmt->execute(['id' => $id]);
$enquiry = $stmt->fetch();

if (!$enquiry) {
    http_response_code(404);
    exit('Enquiry not found.');
}

$productStmt = $pdo->prepare(
    'SELECT p.name, p.slug, p.min_price, p.max_price
     FROM enquiry_products ep
     INNER JOIN products p ON p.id = ep.product_id
     WHERE ep.enquiry_id = :enquiry_id
     ORDER BY p.name'
);
$productStmt->execute(['enquiry_id' => $id]);
$products = $productStmt->fetchAll();

function detailRow(string $label, ?string $value): void
{
    echo '<div class="detail-row"><span>' .
        adminEscape($label) .
        '</span><strong>' .
        nl2br(adminEscape(($value !== null && $value !== '') ? $value : '—')) .
        '</strong></div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View Enquiry | Tribe Green</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f7f9f3; color: #26352b; }
        .detail-page { padding: 35px 0 60px; }
        .detail-card { padding: 25px; border: 1px solid #e4e9df; border-radius: 12px; background: white; }
        .detail-row { display: grid; grid-template-columns: minmax(125px, 0.7fr) minmax(0, 1.5fr); gap: 15px; padding: 14px 0; border-bottom: 1px solid #edf0e9; }
        .detail-row:last-child { border-bottom: 0; }
        .detail-row span { color: #737b72; font-size: 13px; }
        .detail-row strong { font-size: 13px; font-weight: 600; overflow-wrap: anywhere; }
        .status-form { padding: 22px; border: 1px solid #e4e9df; border-radius: 12px; background: white; }
        @media(max-width:575px) { .detail-card { padding: 18px; } .detail-row { grid-template-columns: 1fr; gap: 5px; } }
    </style>
</head>
<body>
<header class="bg-white border-bottom py-3">
    <div class="container d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <a class="navbar-brand brand-logo" href="index.php">
            <span class="brand-mark">T</span>
            <span>tribe<span class="brand-green">green</span></span>
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="index.php">← All enquiries</a>
    </div>
</header>

<main class="detail-page">
    <div class="container">
        <div class="mb-4">
            <span class="text-muted small">ENQUIRY REFERENCE</span>
            <h1 class="h3 fw-bold mt-2"><?= adminEscape($enquiry['reference_no']) ?></h1>
            <p class="text-muted mb-0">
                Received <?= adminEscape(date('d M Y, h:i A', strtotime($enquiry['created_at']))) ?>
            </p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">Enquiry status updated successfully.</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= adminEscape($error) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="detail-card">
                    <h2 class="h5 fw-bold mb-3">Customer information</h2>
                    <?php
                    detailRow('Full name', $enquiry['full_name']);
                    detailRow('Company', $enquiry['company_name']);
                    detailRow('Phone', $enquiry['phone']);
                    detailRow('Email', $enquiry['email']);
                    detailRow('Occasion', $enquiry['occasion_name']);
                    detailRow('Quantity', (string) $enquiry['quantity']);
                    detailRow('Budget per gift', $enquiry['budget_range']);
                    detailRow('Delivery date', $enquiry['delivery_date']);
                    detailRow('Customization', $enquiry['customization_details']);
                    detailRow('Additional message', $enquiry['message']);
                    detailRow('Source', $enquiry['source']);
                    detailRow('Current status', $enquiry['status']);
                    ?>

                    <h2 class="h5 fw-bold mt-4 mb-3">Selected products</h2>
                    <?php if (!$products): ?>
                        <p class="text-muted">No products are linked to this enquiry.</p>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="py-3 border-top">
                                <strong><?= adminEscape($product['name']) ?></strong>
                                <div class="small text-muted mt-1">
                                    ₹<?= number_format((float) $product['min_price']) ?>
                                    – ₹<?= number_format((float) $product['max_price']) ?>
                                </div>
                                <a class="small" href="../product-details.php?slug=<?= rawurlencode($product['slug']) ?>">
                                    View product
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="status-form">
                    <h2 class="h5 fw-bold mb-3">Update enquiry status</h2>
                    <p class="text-muted small">Choose the latest status for this customer enquiry.</p>

                    <form method="post">
                        <input type="hidden" name="csrf_token"
                               value="<?= adminEscape($_SESSION['admin_csrf']) ?>">

                        <label class="form-label" for="status">Status</label>
                        <select class="form-select mb-3" name="status" id="status" required>
                            <?php foreach ($allowedStatuses as $option): ?>
                                <option value="<?= adminEscape($option) ?>"
                                    <?= $enquiry['status'] === $option ? 'selected' : '' ?>>
                                    <?= adminEscape($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button type="submit" class="btn btn-brand w-100">
                            Save status
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</main>
</body>
</html>
