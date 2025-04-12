<?php
/**
 * Flowmodoro Card Shortcode
 * 
 * @package Flowmodoro
 */
function flowmodoro_card_shortcode() {
    ob_start();
    ?>
    <style>
        .flowmodoro-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border-radius: 16px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            background: linear-gradient(145deg, #FFFFFF, #222);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            color: #f0f0f0;
        }

        .flowmodoro-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .flowmodoro-card img {
            max-width: 80px;
            margin-bottom: 15px;
        }

        .flowmodoro-card h3 {
            font-size: 1.4rem;
            margin-bottom: 10px;
        }

        .flowmodoro-card p {
            font-size: 0.95rem;
            margin: 0;
        }

        .flowmodoro-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
    </style>

    <div class="flowmodoro-grid">
        <div class="flowmodoro-card" onclick="window.location.href='https://ascomany.com/flowmodoro/'">
            <img src="<?php echo plugin_dir_url(__FILE__) . 'logo.png'; ?>" alt="Flowmodoro logo">
            <h3>Flowmodoro</h3>
            <p>Lance une session de travail ou consulte ton historique</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('flowmodoro_card', 'flowmodoro_card_shortcode');
