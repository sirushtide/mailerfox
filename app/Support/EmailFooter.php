<?php

namespace App\Support;

use App\Models\Customer;

class EmailFooter
{
    /**
     * Resolve the configured customer-group email footer text for a customer.
     *
     * Walks the customer's groupSettings() (merged across all groups) and
     * returns the first non-empty `email_footer.content` value. Returns an
     * empty string when none is configured.
     */
    public static function forCustomer(?Customer $customer): string
    {
        if (!$customer) {
            return '';
        }

        try {
            $settings = $customer->groupSettings();
        } catch (\Throwable $e) {
            return '';
        }

        $content = data_get($settings, 'email_footer.content', '');

        return is_string($content) ? trim($content) : '';
    }

    /**
     * Resolve the customer-group footer by customer id (lightweight lookup).
     */
    public static function forCustomerId(?int $customerId): string
    {
        if (!$customerId) {
            return '';
        }

        $customer = Customer::find($customerId);

        return self::forCustomer($customer);
    }

    /**
     * Append the footer to an HTML email body, inserting before </body>
     * when present. Plain-text footers are wrapped in a styled div.
     */
    public static function appendHtml(string $body, string $footer): string
    {
        $footer = trim($footer);
        if ($footer === '') {
            return $body;
        }

        $footerIsHtml = preg_match('/<\s*\w+[^>]*>/', $footer) === 1;
        $normalizedFooter = $footerIsHtml
            ? $footer
            : '<div style="white-space: pre-wrap; margin-top: 24px;">' . nl2br(e($footer)) . '</div>';

        if ($body === '') {
            return $normalizedFooter;
        }

        if (stripos($body, '</body>') !== false) {
            return str_ireplace('</body>', $normalizedFooter . '</body>', $body);
        }

        return $body . $normalizedFooter;
    }

    /**
     * Append the footer to a plain-text email body.
     */
    public static function appendText(string $body, string $footer): string
    {
        $footer = trim($footer);
        if ($footer === '') {
            return $body;
        }

        // Strip HTML tags so the plain-text body stays text-only.
        $textFooter = trim(preg_replace('/\s+/', ' ', strip_tags($footer)) ?? '');
        if ($textFooter === '') {
            return $body;
        }

        if (trim($body) === '') {
            return $textFooter;
        }

        return rtrim($body) . "\n\n" . $textFooter;
    }
}
