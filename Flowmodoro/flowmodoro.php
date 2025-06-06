<?php
/*
Plugin Name: Flowmodoro
Description: Timer Flowmodoro
Version: 7.1
Author: Ascomany
*/

$timer = plugin_dir_path(__FILE__) . 'includes/shortcode-timer.php';
$history = plugin_dir_path(__FILE__) . 'includes/shortcode-history.php';
$stats = plugin_dir_path(__FILE__) . 'includes/shortcode-stats.php'; 
$card = plugin_dir_path(__FILE__) . 'includes/shortcode-card.php';

if (file_exists($timer)) require_once $timer;
if (file_exists($history)) require_once $history;
if (file_exists($stats)) require_once $stats;
if (file_exists($card)) require_once $card;

add_action('wp_ajax_save_flowmodoro', function () {
    if (!is_user_logged_in()) wp_send_json_error('Non connecté');

    $user_id = get_current_user_id();
    $history = $_POST['history'] ?? [];
    update_user_meta($user_id, 'flowmodoro_history', $history);
    wp_send_json_success();
});

add_action('wp_ajax_flowmodoro_send_feedback', 'flowmodoro_send_feedback');
add_action('wp_ajax_nopriv_flowmodoro_send_feedback', 'flowmodoro_send_feedback');

function flowmodoro_send_feedback() {
    $type = sanitize_text_field($_POST['type'] ?? '');
    $message = sanitize_textarea_field($_POST['text'] ?? '');

    if (empty($message)) {
        wp_send_json_error("Message vide.");
    }

    $user = wp_get_current_user();
    $email = get_option('admin_email');
    $subject = "[Flowmodoro] Nouveau retour : $type";
    $body = "Type : $type\n\nMessage :\n$message\n\n";
    if ($user && $user->exists()) {
        $body .= "\nUtilisateur connecté : {$user->user_login} ({$user->user_email})";
    }

    wp_mail($email, $subject, $body);
    wp_send_json_success("Feedback envoyé !");
}
