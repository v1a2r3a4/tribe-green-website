<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('tgSendEmail')) {
    /**
     * Send an email using the configured Gmail SMTP account.
     */
    function tgSendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody
    ): bool {
        $config = require __DIR__ . '/../config/mail.php';

        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = $config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['username'];
            $mail->Password   = $config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $config['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(
                $config['from_email'],
                $config['from_name']
            );

            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $plainBody;

            $mail->send();

            return true;
        } catch (\Throwable $e) {
            // Keep SMTP errors out of the customer-facing page.
            error_log(
                '[Tribe Green email] ' .
                get_class($e) . ': ' . $e->getMessage()
            );

            return false;
        }
    }
}

if (!function_exists('tgSendEnquiryNotifications')) {
    /**
     * Send the team notification and customer confirmation separately.
     *
     * Expected enquiry keys:
     * reference_no, full_name, company_name, phone, email,
     * quantity, budget_range, delivery_date,
     * customization_details, message, occasion_name
     */
    function tgSendEnquiryNotifications(
        array $enquiry,
        array $productNames = []
    ): array {
        $escape = static fn ($value): string =>
            htmlspecialchars(
                (string) ($value ?? ''),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );

        $reference = (string) ($enquiry['reference_no'] ?? '');
        $name      = (string) ($enquiry['full_name'] ?? '');
        $email     = trim((string) ($enquiry['email'] ?? ''));

        $products = $productNames
            ? implode(', ', $productNames)
            : 'Please check the admin dashboard';

        $details = [
            'Reference number' => $reference,
            'Customer'         => $name,
            'Company'          => $enquiry['company_name'] ?? '',
            'Phone'            => $enquiry['phone'] ?? '',
            'Email'            => $email,
            'Occasion'          => $enquiry['occasion_name'] ?? '',
            'Products'          => $products,
            'Quantity'          => $enquiry['quantity'] ?? '',
            'Budget'            => $enquiry['budget_range'] ?? '',
            'Delivery date'     => $enquiry['delivery_date'] ?? '',
            'Customization'     => $enquiry['customization_details'] ?? '',
            'Message'            => $enquiry['message'] ?? '',
        ];

        $rows = '';

        foreach ($details as $label => $value) {
            $rows .= '<tr>'
                . '<th style="padding:9px;text-align:left;'
                . 'border:1px solid #ddd;">'
                . $escape($label)
                . '</th>'
                . '<td style="padding:9px;border:1px solid #ddd;">'
                . nl2br($escape($value))
                . '</td></tr>';
        }

        $teamBody = '
            <div style="font-family:Arial,sans-serif;color:#26352c;">
                <h2 style="color:#176b45;">New Tribe Green Enquiry</h2>
                <p>A new customer enquiry has been submitted.</p>
                <table style="border-collapse:collapse;width:100%;">'
                . $rows .
                '</table>
                <p>Please review this enquiry in the admin dashboard.</p>
            </div>';

        $plainDetails = '';

        foreach ($details as $label => $value) {
            $plainDetails .= $label . ': '
                . (is_scalar($value) ? (string) $value : '')
                . "\n";
        }

        $teamConfig = require __DIR__ . '/../config/mail.php';

        $teamSent = tgSendEmail(
            $teamConfig['team_email'],
            $teamConfig['team_name'],
            'New Enquiry: ' . $reference,
            $teamBody,
            "A new Tribe Green enquiry was received.\n\n"
                . $plainDetails
        );

        $customerSent = false;

        // Do not attempt to send to an invalid or missing address.
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $customerBody = '
                <div style="font-family:Arial,sans-serif;'
                    . 'color:#26352c;line-height:1.6;">'
                    . '<h2 style="color:#176b45;">'
                    . 'Thank you for contacting Tribe Green!'
                    . '</h2>'
                    . '<p>Hello ' . $escape($name) . ',</p>'
                    . '<p>We have received your corporate gifting enquiry.'
                    . ' Our team will review your requirements and contact you.</p>'
                    . '<p><strong>Your reference number:</strong> '
                    . $escape($reference) . '</p>'
                    . '<p>Thank you for considering Tribe Green.</p>'
                    . '<p>Regards,<br>Tribe Green Team</p>'
                    . '</div>';

            $customerSent = tgSendEmail(
                $email,
                $name,
                'We received your enquiry - Tribe Green',
                $customerBody,
                "Hello {$name},\n\n"
                    . "We received your Tribe Green enquiry.\n"
                    . "Reference number: {$reference}\n\n"
                    . "Our team will contact you soon.\n\n"
                    . "Regards,\nTribe Green Team"
            );
        } else {
            error_log(
                '[Tribe Green email] Customer email is missing or invalid '
                . 'for enquiry ' . $reference
            );
        }

        return [
            'team_sent'     => $teamSent,
            'customer_sent' => $customerSent,
        ];
    }
}
