<?php
/**
 * Flowmodoro Statistics Shortcode
 * 
 * @package Flowmodoro
 */

function flowmodoro_stats_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Vous devez être connecté pour consulter vos statistiques.</p>';
    }

    ob_start();

    $user_id = get_current_user_id();
    $history = get_user_meta($user_id, 'flowmodoro_history', true);
    $entries = is_string($history) ? json_decode($history, true) : $history;
    if (!is_array($entries)) $entries = [];

    // Stats simples
    $totalWork = 0;
    $totalPause = 0;
    foreach ($entries as $e) {
        if (!is_array($e) || !isset($e['type'], $e['duration'])) continue;
        if ($e['type'] === 'Travail') $totalWork += (int) $e['duration'];
        if ($e['type'] === 'Pause') $totalPause += (int) $e['duration'];
    }

    function format_ms($ms) {
        $sec = floor($ms / 1000);
        $h = floor($sec / 3600);
        $m = floor(($sec % 3600) / 60);
        $s = $sec % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    ?>
    <div class="flowmodoro-stats-container" style="padding: 30px; max-width: 600px; margin: auto;">
        <h2>📊 Statistiques Flowmodoro</h2>
        <ul style="list-style: none; padding: 0; font-family: monospace; font-size: 18px;">
            <li><strong>Total travail :</strong> <?php echo format_ms($totalWork); ?></li>
            <li><strong>Total pause :</strong> <?php echo format_ms($totalPause); ?></li>
        </ul>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('flowmodoro_stats', 'flowmodoro_stats_shortcode');
