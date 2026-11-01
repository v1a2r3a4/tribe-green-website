
<?php
$referenceNo = trim($_GET['ref'] ?? '');

if (!preg_match('/^TG-\d{8}-[A-F0-9]{8}$/', $referenceNo)) {
    header('Location: products.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
$seo = [
    'title' => 'Thank You for Your Enquiry | Tribe Green',
    'description' => 'Thank you for contacting Tribe Green. Our corporate gifting team will get in touch with you soon.',
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
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .thank-you-page {
            display: grid;
            min-height: 65vh;
            padding: 65px 20px;
            place-items: center;
            background: #f8f9f5;
        }

        .thank-you-card {
            width: 100%;
            max-width: 600px;
            padding: 45px 30px;
            border: 1px solid #e5eae0;
            border-radius: 14px;
            background: #fff;
            text-align: center;
        }

        .thank-you-icon {
            display: grid;
            width: 72px;
            height: 72px;
            margin: 0 auto 25px;
            place-items: center;
            border-radius: 50%;
            color: #245b42;
            background: #eaf2e5;
            font-size: 34px;
            font-weight: 800;
        }

        .thank-you-card h1 {
            margin: 15px 0;
            font-family: 'Manrope', sans-serif;
            font-size: clamp(28px, 4vw, 40px);
            font-weight: 800;
            letter-spacing: -1px;
        }

        .thank-you-card p {
            color: #737b72;
            font-size: 14px;
            line-height: 1.8;
        }

        .reference-box {
            margin: 25px 0;
            padding: 20px;
            border: 1px dashed #cad9c3;
            border-radius: 8px;
            background: #f7f9f3;
        }

        .reference-box small {
            display: block;
            margin-bottom: 8px;
            color: #737b72;
            font-size: 11px;
            font-weight: 700;
        }

        .reference-box strong {
            color: #245b42;
            font-size: 21px;
            letter-spacing: 1px;
            overflow-wrap: anywhere;
        }
    </style>
</head>
<body>

<header class="site-header">
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand brand-logo" href="index.php">
                <span class="brand-mark">T</span>
                <span>tribe<span class="brand-green">green</span></span>
            </a>
        </div>
    </nav>
</header>

<main class="thank-you-page">
    <section class="thank-you-card">
        <div class="thank-you-icon" aria-hidden="true">✓</div>

        <span class="eyebrow">ENQUIRY RECEIVED</span>

        <h1>Thank you for reaching out!</h1>

        <p>
            Your corporate gifting enquiry has been submitted successfully.
            Please keep the reference number below for your records.
        </p>

        <div class="reference-box">
            <small>YOUR ENQUIRY REFERENCE</small>
            <strong><?= e($referenceNo) ?></strong>
        </div>

        <p>Our team can use this reference to identify your enquiry.</p>

        <a href="products.php" class="btn btn-brand">
            Continue browsing gifts →
        </a>
    </section>
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

</body>
</html>
