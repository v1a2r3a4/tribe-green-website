<?php

require_once __DIR__ . '/../app/config/database.php';

$whatsappNumber = '91XXXXXXXXXX';

$productName = $product['name'] ?? 'Corporate Gift';
$productSlug = $product['slug'] ?? '';

$productUrl = 'https://YOUR-LIVE-DOMAIN.com/tribe-green/public/product-details.php?slug='
    . rawurlencode($productSlug);

$whatsappMessage = "Hello Tribe Green! I'm interested in the "
    . $productName
    . ". Could you please share more details and corporate pricing?\n"
    . "Product link: " . $productUrl;

$whatsappUrl = 'https://wa.me/' . $whatsappNumber
    . '?text=' . rawurlencode($whatsappMessage);
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    header('Location: products.php');
    exit;
}

// Fetch one active product and its category.
$productStmt = $pdo->prepare(
    'SELECT
        p.id,
        p.name,
        p.slug,
        p.image_path,
        p.short_description,
        p.description,
        p.min_price,
        p.max_price,
        p.customization_options,
        c.name AS category_name,
        c.slug AS category_slug
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     WHERE p.slug = :slug
       AND p.is_active = 1
     LIMIT 1'
);

$productStmt->execute(['slug' => $slug]);
$product = $productStmt->fetch();

if (!$product) {
    http_response_code(404);
    $notFound = true;
} else {
    $notFound = false;

    $occasionStmt = $pdo->prepare(
        'SELECT o.name, o.slug
         FROM occasions o
         INNER JOIN product_occasions po
             ON po.occasion_id = o.id
         WHERE po.product_id = :product_id
         ORDER BY o.name'
    );

    $occasionStmt->execute(['product_id' => $product['id']]);
    $occasions = $occasionStmt->fetchAll();

    $relatedStmt = $pdo->prepare(
        'SELECT id, name, slug, image_path,
                short_description, min_price, max_price
         FROM products
         WHERE category_id = (
             SELECT category_id
             FROM products
             WHERE id = :product_id
         )
           AND id != :exclude_id
           AND is_active = 1
         ORDER BY id DESC
         LIMIT 4'
    );

    $relatedStmt->execute([
        'product_id' => $product['id'],
        'exclude_id' => $product['id'],
    ]);

    $relatedProducts = $relatedStmt->fetchAll();
}

$categoryIcons = [
    'Drinkware' => '☕',
    'Apparel' => '👕',
    'Work Essentials' => '💼',
    'Wellness' => '🌿',
];
$seo = [
    'title' => $product['name'] . ' | Tribe Green',
    'description' => $product['short_description'],
];
$pageTitle = $notFound
    ? 'Product Not Found | Tribe Green'
    : $product['name'] . ' | Tribe Green';

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
    <link rel="stylesheet" href="assets/css/product-details.css">
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

                <a href="index.php#contact" class="btn btn-brand">
                    Get a Quote ↗
                </a>
            </div>
        </div>
    </nav>
</header>

<main>
    <?php if ($notFound): ?>

        <section class="product-not-found">
            <div class="container text-center">
                <span class="eyebrow">PRODUCT NOT FOUND</span>
                <h1>We couldn't find that gift.</h1>
                <p>The product may have been removed or is no longer available.</p>
                <a href="products.php" class="btn btn-brand">
                    Browse all gifts
                </a>
            </div>
        </section>

    <?php else: ?>

        <section class="product-detail-section">
            <div class="container">

                <div class="detail-breadcrumb">
                    <a href="index.php">Home</a>
                    <span>/</span>
                    <a href="products.php">Our Gifts</a>
                    <span>/</span>
                    <a href="products.php?category=<?= rawurlencode($product['category_slug']) ?>">
                        <?= e($product['category_name']) ?>
                    </a>
                    <span>/</span>
                    <span><?= e($product['name']) ?></span>
                </div>

                <div class="row g-5 product-detail-grid">

                    <div class="col-lg-6">
                        <div class="detail-image">
                            <?php if (!empty($product['image_path'])): ?>
                                <img
                                    src="<?= e($product['image_path']) ?>"
                                    alt="<?= e($product['name']) ?>"
                                >
                            <?php else: ?>
                                <div class="detail-image-placeholder">
                                    <span class="detail-placeholder-icon">
                                        <?= e($categoryIcons[$product['category_name']] ?? '✦') ?>
                                    </span>
                                    <span class="detail-placeholder-brand">
                                        TRIBE GREEN
                                    </span>
                                </div>
                            <?php endif; ?>

                            <span class="detail-category">
                                <?= e($product['category_name']) ?>
                            </span>
                        </div>

                        <div class="detail-image-caption">
                            <span>✳</span>
                            Personalise it to reflect your brand.
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="detail-information">

                            <span class="eyebrow">THOUGHTFULLY CURATED</span>

                            <h1><?= e($product['name']) ?></h1>

                            <p class="detail-short-description">
                                <?= e($product['short_description']) ?>
                            </p>

                            <div class="detail-price">
                                <span class="detail-price-label">Indicative price range</span>
                                <strong>
                                    ₹<?= number_format((float) $product['min_price']) ?>
                                    – ₹<?= number_format((float) $product['max_price']) ?>
                                </strong>
                                <small>
                                    Final pricing may vary by order quantity,
                                    customization and packaging.
                                </small>
                            </div>

                            <div class="detail-divider"></div>

                            <div class="detail-description">
                                <h2>About this gift</h2>
                                <p>
                                    <?= nl2br(e(
                                        $product['description']
                                        ?: $product['short_description']
                                    )) ?>
                                </p>
                            </div>

                            <div class="detail-customization">
                                <h2>Customization options</h2>
                                <p>
                                    <?= e(
                                        $product['customization_options']
                                        ?: 'Contact us to discuss available customization options.'
                                    ) ?>
                                </p>
                            </div>

                            <?php if (count($occasions) > 0): ?>
                                <div class="detail-occasions">
                                    <h2>Suitable for</h2>
                                    <div class="occasion-tags">
                                        <?php foreach ($occasions as $occasion): ?>
                                            <a
                                                href="products.php?occasion=<?= rawurlencode($occasion['slug']) ?>"
                                                class="occasion-tag"
                                            >
                                                <?= e($occasion['name']) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="detail-actions">
                                <a
                                    class="btn btn-brand btn-lg"
                                    href="enquiry.php?product_id=<?= (int) $product['id'] ?>"
                                >
                                    Enquire about this gift
                                    <span aria-hidden="true">→</span>
                                </a>


<a href="<?= htmlspecialchars($whatsappUrl, ENT_QUOTES, 'UTF-8') ?>"
   class="btn btn-brand btn-lg"
   target="_blank"
   rel="noopener noreferrer">
    Chat on WhatsApp
</a>
                                <a
                                    class="detail-back-link"
                                    href="products.php"
                                >
                                    ← Back to catalogue
                                </a>
                            </div>

                            <div class="detail-trust-note">
                                <span>✓</span>
                                Bulk orders and custom branding enquiries welcome.
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php if (count($relatedProducts) > 0): ?>
            <section class="related-products-section">
                <div class="container">
                    <div class="section-heading">
                        <div>
                            <span class="eyebrow">KEEP EXPLORING</span>
                            <h2>More from this collection</h2>
                            <p>Discover other gifts in the same category.</p>
                        </div>

                        <a
                            href="products.php?category=<?= rawurlencode($product['category_slug']) ?>"
                            class="text-link"
                        >
                            View category →
                        </a>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($relatedProducts as $related): ?>
                            <div class="col-6 col-lg-3">
                                <article class="product-card">
                                    <a
                                        class="catalogue-product-image"
                                        href="product-details.php?slug=<?= rawurlencode($related['slug']) ?>"
                                    >
                                        <?php if (!empty($related['image_path'])): ?>
                                            <img
                                                src="<?= e($related['image_path']) ?>"
                                                alt="<?= e($related['name']) ?>"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <div class="product-placeholder">
                                                <span class="product-placeholder-icon">
                                                    <?= e($categoryIcons[$product['category_name']] ?? '✦') ?>
                                                </span>
                                                <span class="placeholder-brand">TRIBE GREEN</span>
                                            </div>
                                        <?php endif; ?>
                                    </a>

                                    <div class="product-info">
                                        <h3>
                                            <a href="product-details.php?slug=<?= rawurlencode($related['slug']) ?>">
                                                <?= e($related['name']) ?>
                                            </a>
                                        </h3>

                                        <p><?= e($related['short_description']) ?></p>

                                        <div class="product-price">
                                            ₹<?= number_format((float) $related['min_price']) ?>
                                            – ₹<?= number_format((float) $related['max_price']) ?>
                                        </div>

                                        <a
                                            class="product-link"
                                            href="product-details.php?slug=<?= rawurlencode($related['slug']) ?>"
                                        >
                                            View details →
                                        </a>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

    <?php endif; ?>
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