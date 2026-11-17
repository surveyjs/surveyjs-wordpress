<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_Embed {

    const ACTION = 'surveyjs_embed_page';
    const PARAM = 'surveyjs_embed';
    const CAPABILITY = 'manage_options';

    public static function init() {
        add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_create_page' ) );
        add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_script' ) );
        add_action( 'admin_notices', array( __CLASS__, 'render_classic_editor_notice' ) );
    }

    public static function shortcode( $form_id ) {
        return '[surveyjs id="' . (int) $form_id . '"]';
    }

    public static function create_page_url( $form_id ) {
        return wp_nonce_url( add_query_arg( array( 'action' => self::ACTION, 'form' => (int) $form_id ), admin_url( 'admin-post.php' ) ), self::ACTION . '_' . (int) $form_id );
    }

    public static function pages() {
        return get_posts( array(
            'post_type'      => 'page',
            'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
            'posts_per_page' => 100,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'no_found_rows'  => true,
        ) );
    }

    // The same shortcode the block writes when a form is picked in it (block/block.js getShortcode).
    private static function block_shortcode( $form ) {
        return '[surveyjs id="' . (int) $form->id . '" title="' . str_replace( array( '"', '[', ']' ), '', $form->name ) . '"] ';
    }

    private static function block_markup( $form ) {
        return serialize_block( array(
            'blockName'    => 'sjs/gutenberg-block',
            'attrs'        => array(
                'shortcode' => self::block_shortcode( $form ),
                'surveyid'  => (string) $form->id,
            ),
            'innerBlocks'  => array(),
            'innerHTML'    => '',
            'innerContent' => array(),
        ) );
    }

    public static function handle_create_page() {
        $form_id = absint( $_GET['form'] ?? 0 );
        check_admin_referer( self::ACTION . '_' . $form_id );
        $form = SurveyJS_Forms::find( $form_id );
        if ( ! $form || ! current_user_can( self::CAPABILITY ) || ! current_user_can( 'edit_pages' ) ) {
            wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'surveyjs' ), 403 );
        }
        $page_id = wp_insert_post( wp_slash( array(
            'post_type'    => 'page',
            'post_status'  => 'draft',
            'post_title'   => $form->name,
            'post_content' => use_block_editor_for_post_type( 'page' ) ? self::block_markup( $form ) : self::shortcode( $form->id ),
        ) ), true );
        if ( is_wp_error( $page_id ) ) {
            wp_die( esc_html( $page_id->get_error_message() ) );
        }
        wp_safe_redirect( get_edit_post_link( $page_id, 'raw' ) );
        exit;
    }

    private static function requested_form() {
        $form_id = absint( $_GET[ self::PARAM ] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only adds an unsaved block in the editor; nothing is stored until the user saves the page.
        return $form_id && current_user_can( self::CAPABILITY ) ? SurveyJS_Forms::find( $form_id ) : null;
    }

    public static function enqueue_block_editor_script() {
        $form = self::requested_form();
        if ( ! $form ) {
            return;
        }
        $data = array(
            'surveyid'  => (string) $form->id,
            'shortcode' => self::block_shortcode( $form ),
            'param'     => self::PARAM,
            /* translators: %s: form name */
            'notice'    => sprintf( __( 'The form "%s" was added at the end of the page. Save or update the page to keep it.', 'surveyjs' ), $form->name ),
        );
        wp_add_inline_script( SurveyJS_SurveyJS::BLOCK_SCRIPT, 'var surveyjsEmbed = ' . wp_json_encode( $data ) . ';' . <<<'JS'
wp.domReady(function () {
    var inserted = false;
    var unsubscribe = wp.data.subscribe(function () {
        var editor = wp.data.select("core/editor");
        var blockEditor = wp.data.select("core/block-editor");
        var ready = (!editor.__unstableIsEditorReady || editor.__unstableIsEditorReady()) && Boolean(editor.getCurrentPostId());
        // The editor is ready before the saved blocks of the page are loaded; inserting earlier would be overwritten by them.
        if (inserted || !ready || (editor.getCurrentPostAttribute("content") && !blockEditor.getBlockCount())) {
            return;
        }
        inserted = true;
        unsubscribe();
        var exists = blockEditor.getClientIdsWithDescendants().some(function (clientId) {
            var block = blockEditor.getBlock(clientId);
            return block && block.name === "sjs/gutenberg-block" && block.attributes.surveyid === surveyjsEmbed.surveyid;
        });
        if (!exists) {
            wp.data.dispatch("core/block-editor").insertBlocks(wp.blocks.createBlock("sjs/gutenberg-block", { surveyid: surveyjsEmbed.surveyid, shortcode: surveyjsEmbed.shortcode }));
        }
        wp.data.dispatch("core/notices").createInfoNotice(surveyjsEmbed.notice, { isDismissible: true });
        var url = new URL(window.location.href);
        url.searchParams.delete(surveyjsEmbed.param);
        window.history.replaceState(null, "", url.toString());
    });
});
JS
        );
    }

    public static function render_classic_editor_notice() {
        global $pagenow, $post;
        $form = 'post.php' === $pagenow ? self::requested_form() : null;
        if ( ! $form || ! $post || use_block_editor_for_post( $post ) ) {
            return;
        }
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <?php
                /* translators: %s: form shortcode */
                echo esc_html( sprintf( __( 'Paste %s where the form should appear, then update the page.', 'surveyjs' ), self::shortcode( $form->id ) ) );
                ?>
            </p>
        </div>
        <?php
    }

    public static function render_panel( $form ) {
        $pages = self::pages();
        $panel_id = 'wp-sjs-embed-panel';
        ?>
        <div class="wp-sjs-embed card" id="<?php echo esc_attr( $panel_id ); ?>" hidden>
            <h2><?php esc_html_e( 'Embed the form', 'surveyjs' ); ?></h2>
            <p class="wp-sjs-embed__unsaved" hidden><?php esc_html_e( 'The form has unsaved changes. They are saved before the page opens.', 'surveyjs' ); ?></p>
            <h3><label for="wp-sjs-embed-shortcode"><?php esc_html_e( 'Shortcode', 'surveyjs' ); ?></label></h3>
            <p class="wp-sjs-embed__row">
                <input type="text" id="wp-sjs-embed-shortcode" class="regular-text code" readonly value="<?php echo esc_attr( self::shortcode( $form->id ) ); ?>" />
                <button type="button" class="button" id="wp-sjs-embed-copy" data-copied="<?php esc_attr_e( 'Copied!', 'surveyjs' ); ?>"><?php esc_html_e( 'Copy', 'surveyjs' ); ?></button>
            </p>
            <h3><?php esc_html_e( 'New page', 'surveyjs' ); ?></h3>
            <p><a class="button button-primary" id="wp-sjs-embed-create" data-sjs-embed-save href="<?php echo esc_url( self::create_page_url( $form->id ) ); ?>"><?php esc_html_e( 'Create a page with this form', 'surveyjs' ); ?></a></p>
            <h3><label for="wp-sjs-embed-page"><?php esc_html_e( 'Add to an existing page', 'surveyjs' ); ?></label></h3>
            <?php if ( $pages ) : ?>
                <form class="wp-sjs-embed__row" method="get" action="<?php echo esc_url( admin_url( 'post.php' ) ); ?>" data-sjs-embed-save>
                    <input type="hidden" name="action" value="edit" />
                    <input type="hidden" name="<?php echo esc_attr( self::PARAM ); ?>" value="<?php echo esc_attr( $form->id ); ?>" />
                    <select id="wp-sjs-embed-page" name="post">
                        <?php foreach ( $pages as $page ) : ?>
                            <option value="<?php echo esc_attr( $page->ID ); ?>"><?php echo esc_html( ( '' !== $page->post_title ? $page->post_title : __( '(no title)', 'surveyjs' ) ) . ( 'publish' === $page->post_status ? '' : ' — ' . get_post_status_object( $page->post_status )->label ) ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button"><?php esc_html_e( 'Add to page', 'surveyjs' ); ?></button>
                </form>
            <?php else : ?>
                <p class="description"><?php esc_html_e( 'There are no pages yet.', 'surveyjs' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
