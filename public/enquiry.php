
<?php
session_start();

require_once __DIR__ . '/../app/config/database.php';
require_once dirname(__DIR__)
    . '/app/helpers/email_notifications.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Create a CSRF token to protect the form.
if (empty($_SESSION['enquiry_csrf_token'])) {
    $_SESSION['enquiry_csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;
$product = null;
$occasions = [];
$selectedProductId = 0;

// Get the product ID from the URL or submitted form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedProductId = (int) ($_POST['product_id'] ?? 0);
} else {
    $selectedProductId = (int) ($_GET['product_id'] ?? 0);
}

// Load the selected active product.
$productStmt = $pdo->prepare(
    'SELECT id, name, slug, image_path, short_description,
            min_price, max_price
     FROM products
     WHERE id = :id AND is_active = 1
     LIMIT 1'
);
$productStmt->execute(['id' => $selectedProductId]);
$product = $productStmt->fetch();

if (!$product) {
    http_response_code(404);
    $errors[] = 'Please select a valid product from our catalogue.';
}

// Load occasions for the dropdown.
$occasionStmt = $pdo->query(
    'SELECT id, name FROM occasions ORDER BY name'
);
$occasions = $occasionStmt->fetchAll();

// Keep submitted values in the form if validation fails.
$form = [
    'full_name' => trim($_POST['full_name'] ?? ''),
    'company_name' => trim($_POST['company_name'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'occasion_id' => trim($_POST['occasion_id'] ?? ''),
    'quantity' => trim($_POST['quantity'] ?? ''),
    'budget_range' => trim($_POST['budget_range'] ?? ''),
    'delivery_date' => trim($_POST['delivery_date'] ?? ''),
    'customization_details' => trim($_POST['customization_details'] ?? ''),
    'message' => trim($_POST['message'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    // Verify the form token.
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['enquiry_csrf_token'], $submittedToken)
    ) {
        $errors[] = 'Your form session has expired. Please refresh the page and try again.';
    }

    // Validate customer details.
    if ($form['full_name'] === '' || mb_strlen($form['full_name']) > 150) {
        $errors[] = 'Please enter your name (maximum 150 characters).';
    }

    if ($form['company_name'] === '' || mb_strlen($form['company_name']) > 200) {
        $errors[] = 'Please enter your company name (maximum 200 characters).';
    }

    if (
        $form['phone'] === '' ||
        !preg_match('/^[0-9+\-\s()]{7,25}$/', $form['phone'])
    ) {
        $errors[] = 'Please enter a valid phone number.';
    }

    if (
        $form['email'] === '' ||
        !filter_var($form['email'], FILTER_VALIDATE_EMAIL) ||
        mb_strlen($form['email']) > 254
    ) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate occasion.
    $occasionId = filter_var(
        $form['occasion_id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $validOccasionIds = array_map(
        'intval',
        array_column($occasions, 'id')
    );

    if ($occasionId === false || !in_array($occasionId, $validOccasionIds, true)) {
        $errors[] = 'Please select an occasion.';
    }

    // Validate quantity.
    $quantity = filter_var(
        $form['quantity'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 1000000]]
    );

    if ($quantity === false) {
        $errors[] = 'Quantity must be between 1 and 1,000,000.';
    }

    // Accept only the budget options shown in the form.
    $allowedBudgets = [
        'Under ₹500 per gift',
        '₹500–₹1,500 per gift',
        '₹1,500–₹3,000 per gift',
        'Above ₹3,000 per gift',
        'Flexible / Need guidance',
    ];

    if (!in_array($form['budget_range'], $allowedBudgets, true)) {
        $errors[] = 'Please select a budget range.';
    }

    // Validate optional delivery date.
    if ($form['delivery_date'] !== '') {
        $deliveryDate = DateTime::createFromFormat(
            '!Y-m-d',
            $form['delivery_date']
        );

        $today = new DateTime('today');

        if (
            !$deliveryDate ||
            $deliveryDate->format('Y-m-d') !== $form['delivery_date'] ||
            $deliveryDate < $today
        ) {
            $errors[] = 'Please choose a valid delivery date that is today or later.';
        }
    }

    if (mb_strlen($form['customization_details']) > 5000) {
        $errors[] = 'Customization details must be 5,000 characters or fewer.';
    }

    if (mb_strlen($form['message']) > 5000) {
        $errors[] = 'Your message must be 5,000 characters or fewer.';
    }

    // Save only if every validation check passes.
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $referenceNo = 'TG-' . date('Ymd') . '-' .
                strtoupper(bin2hex(random_bytes(4)));

            $insertEnquiry = $pdo->prepare(
                'INSERT INTO enquiries (
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
                    :source,
                    :status
                )'
            );

            $insertEnquiry->execute([
                'reference_no' => $referenceNo,
                'full_name' => $form['full_name'],
                'company_name' => $form['company_name'],
                'phone' => $form['phone'],
                'email' => $form['email'],
                'occasion_id' => $occasionId,
                'quantity' => $quantity,
                'budget_range' => $form['budget_range'],
                'delivery_date' => $form['delivery_date'] !== ''
                    ? $form['delivery_date']
                    : null,
                'customization_details' => $form['customization_details'] !== ''
                    ? $form['customization_details']
                    : null,
                'message' => $form['message'] !== ''
                    ? $form['message']
                    : null,
                'source' => 'website',
                'status' => 'New',
            ]);

            $enquiryId = (int) $pdo->lastInsertId();

            $insertProduct = $pdo->prepare(
                'INSERT INTO enquiry_products (enquiry_id, product_id)
                 VALUES (:enquiry_id, :product_id)'
            );

            $insertProduct->execute([
                'enquiry_id' => $enquiryId,
                'product_id' => (int) $product['id'],
            ]);

            $pdo->commit();
            
$notificationResults = [
    'team_sent' => false,
    'customer_sent' => false,
];

try {
    // Build this array from the values already validated
    // and saved by your existing enquiry handler.
    $notificationEnquiry = [
        'reference_no' => $referenceNo,
        'full_name' => $fullName,
        'company_name' => $companyName,
        'phone' => $phone,
        'email' => $email,
        'occasion_name' => $occasionName,
        'quantity' => $quantity,
        'budget_range' => $budgetRange,
        'delivery_date' => $deliveryDate,
        'customization_details' => $customizationDetails,
        'message' => $message,
    ];
$seo = [
    'title' => 'Corporate Gifting Enquiry | Tribe Green',
    'description' => 'Submit your corporate gifting requirements and get customised gifting solutions from Tribe Green.',
];
    // Use the product names that were selected for this enquiry.
    $productNames = $selectedProductNames ?? [];

    $notificationResults = tgSendEnquiryNotifications(
        $notificationEnquiry,
        $productNames
    );
} catch (\Throwable $e) {
    // Email failure must not undo the committed enquiry.
    error_log(
        '[Tribe Green email integration] ' . $e->getMessage()
    );
}

// Continue with your existing successful redirect.
// Do not call $pdo->rollBack() after a successful commit.


            // Rotate the form token after successful submission.
            $_SESSION['enquiry_csrf_token'] = bin2hex(random_bytes(32));

            header(
                'Location: thank-you.php?ref=' . rawurlencode($referenceNo)
            );
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Tribe Green enquiry save failed: ' . $exception->getMessage());
            $errors[] = 'We could not submit your enquiry right now. Please try again.';
        }
    }
}

$pageTitle = 'Product Enquiry | Tribe Green';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   

    <?php require dirname(__DIR__) . '/app/helpers/seo.php'; ?>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/enquiry.css">
</head>
<body>

<header class="site-header">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand brand-logo" href="index.php">
                <span class="brand-mark">T</span>
                <span>tribe<span class="brand-green">green</span></span>
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavigation"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavigation">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="products.php">Our Gifts</a>
                    </li>
                </ul>

                <a href="products.php" class="btn btn-brand">Browse gifts</a>
            </div>
        </div>
    </nav>
</header>

<main class="enquiry-page">
    <div class="container">
        <div class="enquiry-heading">
            <span class="eyebrow">LET'S CREATE SOMETHING THOUGHTFUL</span>
            <h1>Tell us about your gifting needs.</h1>
            <p>Share a few details and our team can help you plan the right gift for your organisation.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Please check the following:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($product): ?>
            <div class="enquiry-layout">
                <section class="enquiry-form-card">
                    <form method="POST" action="enquiry.php" novalidate>
                        <input type="hidden" name="csrf_token"
                               value="<?= e($_SESSION['enquiry_csrf_token']) ?>">
                        <input type="hidden" name="product_id"
                               value="<?= (int) $product['id'] ?>">

                        <div class="form-section-heading">
                            <span>01</span>
                            <div>
                                <h2>Your contact details</h2>
                                <p>Tell us how we can reach you.</p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="full_name">Full name *</label>
                                <input class="form-control" id="full_name" name="full_name"
                                       maxlength="150" autocomplete="name" required
                                       value="<?= e($form['full_name']) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="company_name">Company name *</label>
                                <input class="form-control" id="company_name" name="company_name"
                                       maxlength="200" autocomplete="organization" required
                                       value="<?= e($form['company_name']) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone number *</label>
                                <input class="form-control" id="phone" name="phone"
                                       type="tel" maxlength="25" autocomplete="tel" required
                                       value="<?= e($form['phone']) ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="email">Work email *</label>
                                <input class="form-control" id="email" name="email"
                                       type="email" maxlength="254" autocomplete="email" required
                                       value="<?= e($form['email']) ?>">
                            </div>
                        </div>

                        <div class="form-section-heading">
                            <span>02</span>
                            <div>
                                <h2>Gifting requirements</h2>
                                <p>Help us understand the order you have in mind.</p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="occasion_id">Occasion *</label>
                                <select class="form-select" id="occasion_id" name="occasion_id" required>
                                    <option value="">Select an occasion</option>
                                    <?php foreach ($occasions as $occasion): ?>
                                        <option
                                            value="<?= (int) $occasion['id'] ?>"
                                            <?= (string) $form['occasion_id'] === (string) $occasion['id'] ? 'selected' : '' ?>
                                        >
                                            <?= e($occasion['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="quantity">Required quantity *</label>
                                <input class="form-control" id="quantity" name="quantity"
                                       type="number" min="1" max="1000000" step="1" required
                                       value="<?= e($form['quantity']) ?>"
                                       placeholder="e.g. 100">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="budget_range">Budget per gift *</label>
                                <select class="form-select" id="budget_range" name="budget_range" required>
                                    <option value="">Select your budget</option>
                                    <?php
                                    $budgets = [
                                        'Under ₹500 per gift',
                                        '₹500–₹1,500 per gift',
                                        '₹1,500–₹3,000 per gift',
                                        'Above ₹3,000 per gift',
                                        'Flexible / Need guidance',
                                    ];
                                    foreach ($budgets as $budget):
                                    ?>
                                        <option value="<?= e($budget) ?>"
                                            <?= $form['budget_range'] === $budget ? 'selected' : '' ?>>
                                            <?= e($budget) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="delivery_date">Expected delivery date</label>
                                <input class="form-control" id="delivery_date" name="delivery_date"
                                       type="date" min="<?= date('Y-m-d') ?>"
                                       value="<?= e($form['delivery_date']) ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="customization_details">
                                    Branding and customization
                                </label>
                                <textarea class="form-control" id="customization_details"
                                          name="customization_details" rows="3" maxlength="5000"
                                          placeholder="For example: company logo, colours, gift box or message"><?= e($form['customization_details']) ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="message">Additional message</label>
                                <textarea class="form-control" id="message" name="message"
                                          rows="3" maxlength="5000"
                                          placeholder="Any other requirements or questions?"><?= e($form['message']) ?></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-brand btn-lg enquiry-submit">
                            Submit enquiry <span aria-hidden="true">→</span>
                        </button>

                        <p class="form-privacy-note">
                            Your details will be used to respond to this gifting enquiry.
                        </p>
                    </form>
                </section>

                <aside class="enquiry-product-card">
                    <span class="eyebrow">YOUR SELECTED GIFT</span>

                    <div class="enquiry-product-image">
                        <?php if (!empty($product['image_path'])): ?>
                            <img src="<?= e($product['image_path']) ?>"
                                 alt="<?= e($product['name']) ?>">
                        <?php else: ?>
                            <div class="enquiry-image-placeholder">
                                <span>T</span>
                                <small>TRIBE GREEN</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h2><?= e($product['name']) ?></h2>
                    <p><?= e($product['short_description'] ?? '') ?></p>

                    <div class="enquiry-product-price">
                        ₹<?= number_format((float) $product['min_price']) ?>
                        – ₹<?= number_format((float) $product['max_price']) ?>
                    </div>

                    <small class="price-disclaimer">
                        Indicative price per gift. Final pricing depends on quantity and customization.
                    </small>

                    <a href="product-details.php?slug=<?= rawurlencode($product['slug']) ?>"
                       class="enquiry-change-link">← View product details</a>
                </aside>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                Please return to the <a href="products.php">gift catalogue</a> and select a product before submitting an enquiry.
            </div>
        <?php endif; ?>
    </div>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <a class="navbar-brand brand-logo" href="index.php">
            <span class="brand-mark">T</span>
            <span>tribe<span class="brand-green">green</span></span>
        </a>
        <p>Thoughtful gifting. Meaningful connections.</p>
        <span>© <?= date('Y') ?> Tribe Green</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
