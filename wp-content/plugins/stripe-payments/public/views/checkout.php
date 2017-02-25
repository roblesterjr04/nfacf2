<?php

global $asp_payment_success, $asp_error;
if ($asp_payment_success) {

    echo "<div class='asp-thank-you-page-wrap'>";

    if (!empty($content)) {
        echo $content;
    }

    $output .= "<div class='asp-thank-you-page-msg-wrap' style='background: #dff0d8; border: 1px solid #C9DEC1; margin: 10px 0px; padding: 15px;'>";
    $output .= '<p class="asp-thank-you-page-msg1">Thank you for your payment.</p>';
    $output .= '<p class="asp-thank-you-page-msg2">Here\'s what you purchased: </p>';
    $output .= '<div class="asp-thank-you-page-product-name">Product Name: ' . $post_data['item_name'] . '</div>';
    $output .= '<div class="asp-thank-you-page-qty">Quantity: ' . $post_data['item_quantity'] . '</div>';
    $output .= '<div class="asp-thank-you-page-qty">Amount: ' . $post_data['item_price'] . ' ' . $post_data['currency_code'] . '</div>';
    $output .= '<div class="asp-thank-you-page-txn-id">Transaction ID: ' . $post_data['txn_id'] . '</div>';

    if (!empty($item_url)) {
        $output .= "<div class='asp-thank-you-page-download-link'>Please <a href='" . $item_url . "'>click here</a> to download.</div>";
    }
    $output .= "</div>"; //end of .asp-thank-you-page-msg-wrap

    echo apply_filters('asp_stripe_payments_checkout_page_result', $output, $post_data); //Filter that allows you to modify the output data on the checkout result page

    echo "</div>"; //end of .asp-thank-you-page-wrap
} else {
    echo __("System was not able to complete the payment." . $asp_error);
}
