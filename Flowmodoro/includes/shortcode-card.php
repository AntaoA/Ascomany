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
        .flowmodoro-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            margin: 30px 0;
        }

        .flowmodoro-card {
            aspect-ratio: 1 / 1.2;
            background: linear-gradient(145deg, #1a1a1a, #222);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            text-align: center;
            color: #f0f0f0;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .flowmodoro-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.4);
        }

        .flowmodoro-card img {
            max-width: 60px;
            height: auto;
        }

        .flowmodoro-card h3 {
            font-size: 1.2rem;
            margin: 15px 0 10px;
        }

        .flowmodoro-card p {
            font-size: 0.9rem;
            opacity: 0.8;
            margin: 0;
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
