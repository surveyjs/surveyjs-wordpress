<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class( 'wp-sjs-preview-page' ); ?>>
<?php wp_body_open(); ?>
<div class="wp-site-blocks">
    <main class="wp-sjs-preview-page__main has-global-padding is-layout-constrained">
        <div class="entry-content wp-sjs-plugin">
            <div class="wp-sjs-survey" id="wp-sjs-preview-survey"></div>
        </div>
    </main>
</div>
<?php wp_footer(); ?>
</body>
</html>
