<?php

require_once __DIR__ . '/../app/config/database.php';
$pageTitle = 'Tribe Green | Thoughtful Corporate Gifting';

$categoryStmt = $pdo->query(
    'SELECT id, name, slug FROM categories ORDER BY id ASC'
);
$categories = $categoryStmt->fetchAll();

$productStmt = $pdo->query(
    'SELECT p.id, p.name, p.slug, p.image_path,
            p.short_description, p.min_price, p.max_price,
            c.name AS category_name
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1
     ORDER BY p.id DESC
     LIMIT 8'
);
$products = $productStmt->fetchAll();

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$categoryIcons = [
    'Drinkware' => '☕',
    'Apparel' => '👕',
    'Work Essentials' => '💼',
    'Wellness' => '🌿',
];

$productIcons = [
    'Drinkware' => '♨',
    'Apparel' => '✦',
    'Work Essentials' => '▤',
    'Wellness' => '❋',
];
$seo = [
    'title' => 'Tribe Green | Premium Corporate Gifting Solutions',
    'description' => 'Discover premium corporate gifts, employee welcome kits and customised business gifting solutions with Tribe Green.',
];
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

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >
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
                aria-controls="mainNavigation"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavigation">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#categories">Categories</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" href="products.php">Our Gifts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About Us</a>
                    </li>
                </ul>

                <a class="btn btn-brand" href="#contact">
                    Get a Quote <span aria-hidden="true">↗</span>
                </a>
            </div>
        </div>
    </nav>
</header>

<main>

    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="eyebrow">
                        <span class="eyebrow-dot"></span>
                        CORPORATE GIFTS, THOUGHTFULLY CURATED
                    </span>

                    <h1>
                        Make every<br>
                        connection <span class="heading-accent">count.</span>
                    </h1>

                    <p class="hero-description">
                        Meaningful gifts for the people who make your business
                        special. Discover thoughtful corporate gifting,
                        made personal by Tribe Green.
                    </p>

                    <div class="hero-actions">
                       <a href="products.php" class="btn btn-brand btn-lg">
    Explore our gifts <span aria-hidden="true">→</span>
</a>

                        <a href="#categories" class="text-link">
                            Discover categories
                        </a>
                    </div>

                    <div class="hero-proof">
                        <div class="proof-icon">✓</div>
                        <span>Thoughtful products. Personalised experiences.</span>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="hero-art">
                        <div class="hero-orbit orbit-one"></div>
                        <div class="hero-orbit orbit-two"></div>

                        <div class="hero-label label-top">
                            <span>✦</span> Made for meaningful moments
                        </div>

                        <div class="gift-box">
                            <div class="gift-box-lid"></div>
                            <div class="gift-ribbon"></div>
                            <div class="gift-bow"></div>
                            <div class="gift-box-front">
                                <span>tribe</span>
                                <strong>green</strong>
                            </div>
                        </div>

                        <div class="floating-note note-left">
                            <span class="note-icon">♧</span>
                            <span>
                                <strong>Thoughtful by nature</strong>
                                <small>Gifting with purpose</small>
                            </span>
                        </div>

                        <div class="floating-note note-right">
                            <span class="note-icon">♡</span>
                            <span>
                                <strong>Made personal</strong>
                                <small>Your brand, your way</small>
                            </span>
                        </div>

                        <span class="art-sparkle sparkle-one">✳</span>
                        <span class="art-sparkle sparkle-two">✦</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="benefits-strip">
        <div class="container">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="benefit-item">
                        <span>✳</span> Curated collections
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="benefit-item">
                        <span>♧</span> Personalisation options
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="benefit-item">
                        <span>◇</span> Bulk gifting solutions
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="benefit-item">
                        <span>♡</span> Gifts with meaning
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding" id="categories">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">FIND YOUR PERFECT GIFT</span>
                    <h2>Explore our categories</h2>
                    <p>Considered essentials for every team and occasion.</p>
                </div>

                <a href="#products" class="text-link">
                    Browse all gifts <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="row g-4">
                <?php foreach ($categories as $category): ?>
                    <?php
                    $categoryName = $category['name'];
                    $icon = $categoryIcons[$categoryName] ?? '✦';
                    ?>
                    <div class="col-6 col-lg-3">
                        <a href="#products" class="category-card">
                            <div class="category-art">
                                <span><?= escapeHtml($icon) ?></span>
                            </div>

                            <div class="category-card-footer">
                                <h3><?= escapeHtml($categoryName) ?></h3>
                                <span class="category-arrow" aria-hidden="true">↗</span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section-padding products-section" id="products">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">THE TRIBE GREEN EDIT</span>
                    <h2>Gifts that say more</h2>
                    <p>Discover favourites for your team, clients and partners.</p>
                </div>
            </div>

            <div class="row g-4">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $categoryName = $product['category_name'];
                        $icon = $productIcons[$categoryName] ?? '✦';
                        ?>
                        <div class="col-6 col-lg-3">
                            <article class="product-card">
                                <div class="product-visual">
                                    <?php if (!empty($product['image_path'])): ?>
                                        <img
                                            src="<?= escapeHtml($product['image_path']) ?>"
                                            alt="<?= escapeHtml($product['name']) ?>"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <div class="product-placeholder">
                                            <span class="product-placeholder-icon">
                                                <?= escapeHtml($icon) ?>
                                            </span>
                                            <span class="placeholder-brand">
                                                TRIBE GREEN
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <span class="product-category">
                                        <?= escapeHtml($categoryName) ?>
                                    </span>
                                </div>

                                <div class="product-info">
                                    <h3><?= escapeHtml($product['name']) ?></h3>

                                    <p>
                                        <?= escapeHtml($product['short_description']) ?>
                                    </p>

                                    <div class="product-price">
                                        <span>
                                            ₹<?= number_format((float) $product['min_price']) ?>
                                            –
                                            ₹<?= number_format((float) $product['max_price']) ?>
                                        </span>
                                    </div>

                                    <a class="product-link" href="#contact">
                                        Enquire about this gift
                                        <span aria-hidden="true">→</span>
                                    </a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p>No products are available right now.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="about-section section-padding" id="about">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="about-art">
                        <span class="about-leaf leaf-one">✳</span>
                        <span class="about-leaf leaf-two">♧</span>
                        <div class="about-art-message">
                            <span>GIVE WITH</span>
                            <strong>intention.</strong>
                            <span>CONNECT WITH HEART.</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <span class="eyebrow">A LITTLE MORE THOUGHTFUL</span>
                    <h2>Because the best gifts mean something.</h2>

                    <p class="about-description">
                        From welcoming new teammates to celebrating lasting
                        partnerships, we help businesses find gifts that
                        feel considered, useful and personal.
                    </p>

                    <a href="#contact" class="btn btn-brand btn-lg">
                        Let's create something special →
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-section section-padding" id="contact">
        <div class="container">
            <div class="contact-card">
                <span class="eyebrow">YOUR NEXT GREAT GIFT STARTS HERE</span>
                <h2>Let's make gifting memorable.</h2>
                <p>
                    Planning an employee welcome kit, a festive collection,
                    or a special client gift? Tell us what you have in mind.
                </p>
                <a
                    class="btn btn-light btn-lg contact-button"
                    href="mailto:hello@tribegreen.com?subject=Corporate%20Gifting%20Enquiry"
                >
                    Discuss your gifting needs <span aria-hidden="true">↗</span>
                </a>
                <small>Replace this demo email with Tribe Green's verified business email.</small>
            </div>
        </div>
    </section>

</main>

<footer class="site-footer">
    <div class="container">
        <div class="footer-inner">
            <a class="navbar-brand brand-logo" href="index.php">
                <span class="brand-mark">T</span>
                <span>tribe<span class="brand-green">green</span></span>
            </a>

            <p>Thoughtful gifting. Meaningful connections.</p>

            <span>© <?= date('Y') ?> Tribe Green</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>

</body>
</html>