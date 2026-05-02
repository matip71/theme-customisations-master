<?php
/**
 * Payment Receipt Upload — WooCommerce integration.
 *
 * Allows customers to upload their bank transfer receipt on the
 * order-received (thank-you) page and the "View Order" page.
 *
 * Security:
 * - Files stored in a private directory (blocked by .htaccess).
 * - Served via PHP with owner / admin permission checks.
 * - Maximum 3 uploads per order to prevent abuse.
 *
 * @package Theme_Customisations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TF_Payment_Receipt {

    const META_KEY          = '_tf_receipt_attachment_id';
    const META_UPLOAD_COUNT = '_tf_receipt_upload_count';
    const AJAX_ACTION       = 'tf_upload_receipt';
    const VIEW_ACTION       = 'tf_view_receipt';
    const MAX_FILE_SIZE     = 5 * 1024 * 1024;
    const MAX_UPLOADS       = 3;
    const PRIVATE_DIR       = 'tf-receipts';

    const ALLOWED_TYPES = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        'webp'         => 'image/webp',
        'pdf'          => 'application/pdf',
    );

    /** @var int Temporary order ID for upload_dir filter. */
    private $current_upload_order_id = 0;

    /**
     * Wire up hooks.
     */
    public function __construct() {
        // Render on thank-you page (BACS, after bank details) and My Account → View Order.
        add_action( 'woocommerce_thankyou_bacs', array( $this, 'render_upload_form' ), 20 );
        add_action( 'woocommerce_view_order',    array( $this, 'render_upload_form' ) );

        // AJAX: upload receipt.
        add_action( 'wp_ajax_' . self::AJAX_ACTION,        array( $this, 'handle_upload' ) );
        add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( $this, 'handle_upload' ) );

        // AJAX: serve private file.
        add_action( 'wp_ajax_' . self::VIEW_ACTION,        array( $this, 'handle_view' ) );
        add_action( 'wp_ajax_nopriv_' . self::VIEW_ACTION, array( $this, 'handle_view' ) );
    }

    // ─── A. Render ──────────────────────────────────────────────────

    /**
     * Output the receipt upload form.
     *
     * @param int $order_id
     */
    public function render_upload_form( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        if ( 'bacs' !== $order->get_payment_method() || 'on-hold' !== $order->get_status() ) {
            return;
        }

        if ( ! $this->current_user_can_access( $order ) ) {
            return;
        }

        // Existing receipt data.
        $existing_id   = (int) $order->get_meta( self::META_KEY );
        $upload_count  = (int) $order->get_meta( self::META_UPLOAD_COUNT );
        $remaining     = max( 0, self::MAX_UPLOADS - $upload_count );
        $existing_url  = '';
        $existing_type = '';

        if ( $existing_id ) {
            $mime          = get_post_mime_type( $existing_id );
            $existing_type = ( 0 === strpos( $mime, 'image/' ) ) ? 'image' : 'pdf';
            $existing_url  = $this->build_view_url( $order );
        }

        wp_localize_script( 'tf-payment-receipt', 'tfReceipt', array(
            'ajax_url'          => admin_url( 'admin-ajax.php' ),
            'nonce'             => wp_create_nonce( self::AJAX_ACTION ),
            'order_id'          => $order_id,
            'order_key'         => $order->get_order_key(),
            'max_size'          => self::MAX_FILE_SIZE,
            'existing_url'      => $existing_url,
            'existing_type'     => $existing_type,
            'remaining_uploads' => $remaining,
            'i18n'              => array(
                'drop_text'     => 'Arrastrá tu comprobante aquí',
                'or_text'       => 'o',
                'browse_text'   => 'Seleccionar archivo',
                'upload_text'   => 'Enviar comprobante',
                'replace_text'  => 'Reemplazar comprobante',
                'uploading'     => 'Subiendo…',
                'success'       => '¡Comprobante enviado correctamente!',
                'error_size'    => 'El archivo supera el tamaño máximo de 5 MB.',
                'error_type'    => 'Formato no soportado. Usá JPG, PNG, WebP o PDF.',
                'error_generic' => 'Error al subir el comprobante. Intentá de nuevo.',
                'error_limit'   => 'Alcanzaste el límite de subidas para este pedido.',
                'remaining_one' => 'Tenés 1 intento restante.',
                'remaining_many'=> 'Tenés %d intentos restantes.',
            ),
        ) );

        ?>
        <div class="tf-receipt-upload" id="tf-receipt-upload">
            <h3><?php esc_html_e( 'Comprobante de pago', 'woocommerce' ); ?></h3>
            <p class="tf-receipt-upload__desc">
                <?php esc_html_e( 'Adjuntá tu comprobante de transferencia para agilizar la verificación de tu pedido. También podes adjuntarlo más adelante desde la sección mis pedidos.', 'woocommerce' ); ?>
            </p>

            <div class="tf-receipt-upload__dropzone" id="tf-receipt-dropzone">
                <div class="tf-receipt-upload__dropzone-inner">
                    <svg class="tf-receipt-upload__icon" xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <p class="tf-receipt-upload__drop-text"></p>
                    <p class="tf-receipt-upload__or-text"></p>
                    <button type="button" class="tf-receipt-upload__browse-btn" id="tf-receipt-browse"></button>
                </div>
                <input type="file" id="tf-receipt-file" accept=".jpg,.jpeg,.png,.webp,.pdf" style="display:none;" />
            </div>

            <div class="tf-receipt-upload__preview" id="tf-receipt-preview" style="display:none;">
                <div class="tf-receipt-upload__preview-inner" id="tf-receipt-preview-inner"></div>
                <button type="button" class="tf-receipt-upload__remove" id="tf-receipt-remove">&times;</button>
            </div>

            <div class="tf-receipt-upload__actions">
                <button type="button" class="tf-receipt-upload__submit" id="tf-receipt-submit" disabled></button>
                <p class="tf-receipt-upload__remaining" id="tf-receipt-remaining"></p>
            </div>

            <div class="tf-receipt-upload__msg" id="tf-receipt-msg" style="display:none;"></div>
        </div>
        <?php
    }

    // ─── B. AJAX: Upload ────────────────────────────────────────────

    /**
     * Handle the receipt file upload via AJAX.
     */
    public function handle_upload() {
        check_ajax_referer( self::AJAX_ACTION, 'nonce' );

        $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( array( 'message' => 'Pedido no encontrado.' ) );
        }

        if ( 'bacs' !== $order->get_payment_method() || 'on-hold' !== $order->get_status() ) {
            wp_send_json_error( array( 'message' => 'Este pedido no acepta comprobantes.' ) );
        }

        if ( ! $this->current_user_can_access( $order ) ) {
            wp_send_json_error( array( 'message' => 'No tenés permiso para esta acción.' ) );
        }

        // ── Upload limit ────────────────────────────────────────
        $upload_count = (int) $order->get_meta( self::META_UPLOAD_COUNT );
        if ( $upload_count >= self::MAX_UPLOADS ) {
            wp_send_json_error( array( 'message' => 'Alcanzaste el límite de subidas para este pedido.' ) );
        }

        if ( empty( $_FILES['receipt'] ) ) {
            wp_send_json_error( array( 'message' => 'No se recibió ningún archivo.' ) );
        }

        $file = $_FILES['receipt'];

        if ( $file['size'] > self::MAX_FILE_SIZE ) {
            wp_send_json_error( array( 'message' => 'El archivo supera el tamaño máximo de 5 MB.' ) );
        }

        $filetype = wp_check_filetype( $file['name'], self::ALLOWED_TYPES );
        if ( empty( $filetype['type'] ) ) {
            wp_send_json_error( array( 'message' => 'Formato no soportado. Usá JPG, PNG, WebP o PDF.' ) );
        }

        // ── Private upload ──────────────────────────────────────
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $this->current_upload_order_id = $order_id;
        $this->ensure_private_dir();
        add_filter( 'upload_dir', array( $this, 'custom_upload_dir' ) );

        $uploaded = wp_handle_upload( $file, array(
            'test_form' => false,
            'mimes'     => self::ALLOWED_TYPES,
        ) );

        remove_filter( 'upload_dir', array( $this, 'custom_upload_dir' ) );

        if ( isset( $uploaded['error'] ) ) {
            wp_send_json_error( array( 'message' => $uploaded['error'] ) );
        }

        // Create attachment.
        $attach_id = wp_insert_attachment(
            array(
                'post_title'     => sprintf( 'Comprobante pedido #%d', $order_id ),
                'post_mime_type' => $uploaded['type'],
                'post_status'    => 'inherit',
            ),
            $uploaded['file'],
            $order_id
        );

        if ( is_wp_error( $attach_id ) ) {
            wp_send_json_error( array( 'message' => 'Error al guardar el archivo.' ) );
        }

        wp_update_attachment_metadata( $attach_id, wp_generate_attachment_metadata( $attach_id, $uploaded['file'] ) );

        // Delete previous receipt.
        $previous_id = (int) $order->get_meta( self::META_KEY );
        if ( $previous_id && $previous_id !== $attach_id ) {
            wp_delete_attachment( $previous_id, true );
        }

        // Save to order + increment counter.
        $order->update_meta_data( self::META_KEY, $attach_id );
        $order->update_meta_data( self::META_UPLOAD_COUNT, $upload_count + 1 );
        $order->save();

        // Order note (link goes through the private view endpoint).
        $view_url = $this->build_view_url( $order );
        $note     = $previous_id
            ? 'El cliente reemplazó el comprobante de pago.'
            : 'El cliente adjuntó un comprobante de pago.';
        $note    .= sprintf( ' <a href="%s" target="_blank">Ver comprobante</a>', esc_url( $view_url ) );
        $order->add_order_note( $note );

        // Notify admin with context.
        $this->notify_admin( $order );

        $is_image  = ( 0 === strpos( $uploaded['type'], 'image/' ) );
        $remaining = max( 0, self::MAX_UPLOADS - ( $upload_count + 1 ) );

        wp_send_json_success( array(
            'message'           => '¡Comprobante enviado correctamente!',
            'url'               => $view_url,
            'type'              => $is_image ? 'image' : 'pdf',
            'remaining_uploads' => $remaining,
        ) );
    }

    // ─── C. AJAX: View (serve private file) ─────────────────────────

    /**
     * Serve the receipt file after verifying permissions.
     * Accessible by the order owner and shop managers.
     */
    public function handle_view() {
        $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_die( 'Pedido no encontrado.', 'Error', array( 'response' => 404 ) );
        }

        if ( ! $this->current_user_can_access( $order ) ) {
            wp_die( 'No tenés permiso para ver este archivo.', 'Error', array( 'response' => 403 ) );
        }

        $attach_id = (int) $order->get_meta( self::META_KEY );
        if ( ! $attach_id ) {
            wp_die( 'No se encontró comprobante.', 'Error', array( 'response' => 404 ) );
        }

        $file_path = get_attached_file( $attach_id );
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            wp_die( 'Archivo no encontrado.', 'Error', array( 'response' => 404 ) );
        }

        $mime = get_post_mime_type( $attach_id );

        // Clean output buffers before streaming.
        while ( ob_get_level() ) {
            ob_end_clean();
        }

        header( 'Content-Type: ' . $mime );
        header( 'Content-Disposition: inline; filename="' . wp_basename( $file_path ) . '"' );
        header( 'Content-Length: ' . filesize( $file_path ) );
        header( 'Cache-Control: private, max-age=3600' );

        readfile( $file_path );
        exit;
    }

    // ─── D. Helpers ─────────────────────────────────────────────────

    /**
     * Check if the current user can access the order.
     * Allows: order owner (logged-in or guest with key) and shop managers.
     *
     * @param  WC_Order $order
     * @return bool
     */
    private function current_user_can_access( $order ) {
        // Admins / shop managers always have access.
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return true;
        }

        // Logged-in customer owns the order.
        if ( is_user_logged_in() ) {
            return (int) $order->get_customer_id() === get_current_user_id();
        }

        // Guest: verify via order key (POST or GET).
        $key = '';
        if ( isset( $_POST['order_key'] ) ) {
            $key = sanitize_text_field( wp_unslash( $_POST['order_key'] ) );
        } elseif ( isset( $_GET['key'] ) ) {
            $key = sanitize_text_field( wp_unslash( $_GET['key'] ) );
        }

        return $key && $order->key_is_valid( $key );
    }

    /**
     * Build the private view URL for a receipt.
     *
     * @param  WC_Order $order
     * @return string
     */
    private function build_view_url( $order ) {
        return add_query_arg( array(
            'action'   => self::VIEW_ACTION,
            'order_id' => $order->get_id(),
            'key'      => $order->get_order_key(),
        ), admin_url( 'admin-ajax.php' ) );
    }

    /**
     * Create the private uploads directory and protect it.
     */
    private function ensure_private_dir() {
        $upload_dir  = wp_upload_dir();
        $private_dir = $upload_dir['basedir'] . '/' . self::PRIVATE_DIR;

        if ( ! file_exists( $private_dir ) ) {
            wp_mkdir_p( $private_dir );
        }

        $htaccess = $private_dir . '/.htaccess';
        if ( ! file_exists( $htaccess ) ) {
            file_put_contents( $htaccess, "Order deny,allow\nDeny from all\n" );
        }

        $index = $private_dir . '/index.php';
        if ( ! file_exists( $index ) ) {
            file_put_contents( $index, "<?php\n// Silence is golden.\n" );
        }
    }

    /**
     * Redirect uploads to the private tf-receipts/{order_id}/ directory.
     * Used as a temporary filter on 'upload_dir'.
     *
     * @param  array $uploads
     * @return array
     */
    public function custom_upload_dir( $uploads ) {
        $subdir            = '/' . self::PRIVATE_DIR . '/' . $this->current_upload_order_id;
        $uploads['subdir'] = $subdir;
        $uploads['path']   = $uploads['basedir'] . $subdir;
        $uploads['url']    = $uploads['baseurl'] . $subdir;

        if ( ! file_exists( $uploads['path'] ) ) {
            wp_mkdir_p( $uploads['path'] );
        }

        return $uploads;
    }

    /**
     * Send a contextual notification email to the site admin.
     *
     * @param WC_Order $order
     */
    private function notify_admin( $order ) {
        $order_id      = $order->get_id();
        $order_url     = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
        $customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        $customer_email = $order->get_billing_email();
        $order_total   = wp_strip_all_tags( $order->get_formatted_order_total() );
        $order_date    = $order->get_date_created()
            ? $order->get_date_created()->date_i18n( 'd/m/Y H:i' )
            : '—';

        $subject = sprintf( 'Comprobante de pago recibido — Pedido #%d', $order_id );
        $message = sprintf(
            "Se recibió un comprobante de pago para el pedido #%d.\n\n" .
            "Cliente: %s (%s)\n" .
            "Total del pedido: %s\n" .
            "Fecha del pedido: %s\n\n" .
            "Revisá el comprobante desde el panel de administración:\n%s\n",
            $order_id,
            $customer_name,
            $customer_email,
            $order_total,
            $order_date,
            $order_url
        );

        wp_mail( get_option( 'admin_email' ), $subject, $message );
    }
}
