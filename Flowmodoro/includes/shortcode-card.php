<?php
/**
 * Flowmodoro Card Shortcode
 * 
 * @package Flowmodoro
 */
function flowmodoro_card_shortcode() {
    ob_start();
    ?>
    <div style="border: 2px solid #4CAF50; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; background-color: #f0fff0; transition: box-shadow 0.3s;" onclick="window.location.href='https://ascomany.com/flowmodoro/'" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
        <h2 style="margin: 0 0 10px;">Lancer Flowmodoro</h2>
        <p style="margin: 0;">Commence une session de travail ou consulte ton historique</p>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('flowmodoro_card', 'flowmodoro_card_shortcode');