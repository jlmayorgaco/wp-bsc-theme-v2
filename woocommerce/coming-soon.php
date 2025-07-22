<?php
// coming-soon.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Pronto regresaremos! - Bubbles Skin Care</title>

    <style>
        :root {
            --color--pink: #f7c0cd;
            --color--black: #333333;
            --font-family: 'Arial', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--color--pink);
            font-family: var(--font-family);
            color: var(--color--black);
        }

        .coming-soon-container {
            text-align: center;
            max-width: 800px;
            padding: 20px;
        }

        .coming-soon-container img {
            max-width: 250px;
            height: auto;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            font-weight: bold;
            letter-spacing: 4px;
        }

        p {
            font-size: 1.2rem;
            line-height: 1.5;
            margin: 0 0 20px;
            letter-spacing: 2px;
        }

        /* Social Media List */
        .social-media-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        /* Each Social Media Icon Item */
        .social-media-list li {
            display: inline-block;
            position: relative;
            padding-right: 20px; /* Space before the vertical line */
        }

        /* Vertical Line Between Icons */
        .social-media-list li:not(:last-child)::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            height: 100%;
            width: 4px;
            background-color: var(--color--black);
            opacity: 1;
        }

        .social-media-list li {
            display: inline-block;
        }

        .social-media-list li:af {
            display: inline-block;
        }

        .social-media-list a {
            display: inline-block;
            width: 40px;
            height: 40px;
        }

      

        .social-media-list img {
            width: 100%;
            height: auto;
            margin-bottom: 0px;
        }

        .social-media-list a:hover img {
            opacity: 0.5;
            cursor: pointer;
        }

        .notify-button {
            background-color: var(--color--black);
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            text-transform: uppercase;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        .notify-button:hover {
            background-color: #000;
        }
    </style>
</head>
<body>
    <div class="coming-soon-container">
        <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/BSC_COMING_SOON_FACE.png" alt="Bubbles Skin Care">
        
        <h1>¡Coming soon!</h1>
        
        <p>Nuestra pagina web está en mantenimiento.</p>
        
        <p><strong>Contactanos en nuestras redes sociales</strong></p>

        <ul class="social-media-list">
            <li>
                <a href="https://www.instagram.com/bubbles.skincare/" target="_blank">
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/BSC_COMING_SOON_SOCIAL_INSTAGRAM.png" alt="Instagram">
                </a>
            </li>
            <li>
                <a href="https://wa.me/573156922859" target="_blank">
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/BSC_COMING_SOON_SOCIAL_WHATSAPP.png" alt="WhatsApp">
                </a>
            </li>
            <li>
                <a href="https://www.tiktok.com/@bubblesskincare" target="_blank">
                    <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/BSC_COMING_SOON_SOCIAL_TIKTOK.png" alt="TikTok">
                </a>
            </li>
        </ul>
    </div>
</body>
</html>
