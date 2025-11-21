<?php
/**
 * Xmoney Payments Custom Transaction Table Class
 *
 * Custom Transaction Class on the Administrator dashboard
 *
 * @package  Xmoney/Admin
 * @category Admin
 * @author   Xmoney Payments
 */

/* Exit if the file is accessed directly. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add the copy of the WP_List_Table class. We made a copy because the class is private.
require_once XMONEY_PAYMENTS_PLUGIN_DIR . 'includes/class-xmoney-payments-list-table.php';

/**
 * Base custom class for displaying a list of items in an ajaxified HTML table.
 */
class Xmoney_Payments_Transaction_Table extends Xmoney_Payments_List_Table {

	/**
	 * Constructor.
	 *
	 * The child class should call this constructor from its own constructor to override
	 * the default $args.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 *     Array or string of arguments.
	 *
	 *     @type string $plural   Plural value used for labels and the objects being listed.
	 *                            This affects things such as CSS class-names and nonces used
	 *                            in the list table, e.g. 'posts'. Default empty.
	 *     @type string $singular Singular label for an object being listed, e.g. 'post'.
	 *                            Default empty
	 *     @type bool   $ajax     Whether the list table supports Ajax. This includes loading
	 *                            and sorting data, for example. If true, the class will call
	 *                            the _js_vars() method in the footer to provide variables
	 *                            to any scripts handling Ajax events. Default false.
	 *     @type string $screen   String containing the hook name used to determine the current
	 *                            screen. If left null, the current screen will be automatically set.
	 *                            Default null.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'notification',
				'plural'   => 'notifications',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Displays the search box.
	 *
	 * @param string $text     The 'submit' button label.
	 * @param string $input_id ID attribute value for the search input field.
	 * @since  3.1.0
	 * @access public
	 */
	public function search_box( string $text, string $input_id ) {
		// Read-only GET parameters for view state. Nonce not required because no state change occurs.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only preservation of sort/filter parameters.
		if ( isset( $_GET['orderby'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only preservation of sort/filter parameters.
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
			if ( '' !== $orderby ) {
				echo '<input type="hidden" name="orderby" value="' . esc_attr( $orderby ) . '" />';
			}
		}
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only.
		if ( isset( $_GET['order'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only.
			$xmoney_payments_order = sanitize_text_field( wp_unslash( $_GET['order'] ) );
			if ( '' !== $xmoney_payments_order ) {
				echo '<input type="hidden" name="order" value="' . esc_attr( $xmoney_payments_order ) . '" />';
			}
		}
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only.
		if ( isset( $_GET['status'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only.
			$status = sanitize_text_field( wp_unslash( $_GET['status'] ) );
			if ( '' !== $status ) {
				echo '<input type="hidden" name="status" value="' . esc_attr( $status ) . '" />';
			}
		}

		?>
			<p class="search-box">
				<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
				<?php submit_button( $text, 'button', '', false, array( 'id' => 'search-submit' ) ); ?>
			</p>
		<?php
	}

	/**
	 * Custom function that retrive the number of Transactions
	 *
	 * @param Object $wpdb         WordPress refference to database.
	 */
	private function get_all_count( object $wpdb ) {
		$table_name = $wpdb->prefix . 'xmoney_payments_transactions';

		$table_name = esc_sql( $table_name );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is fixed and not user input.
		$wpdb->get_results( "SELECT id_tw_transactions FROM {$table_name}" );

		return $wpdb->num_rows;
	}

	/**
	 * Get an associative array ( id => link ) with the list
	 * of views available on this table.
	 *
	 * @since  3.1.0
	 * @access protected
	 *
	 * @return array
	 */
	public function get_views(): array {
		global $wpdb;

		$views = array();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view filter.
		$current = ( ! empty( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all' );

		// All link
		$class        = ( 'all' === $current ? ' class="current"' : '' );
		$all_url      = remove_query_arg( 'status' );
		$views['all'] = "<a href='{$all_url }' {$class} >" . esc_html__( 'All', 'xmoney-payments' ) . "<span class='view_count'> ( " . esc_attr( $this->get_all_count( $wpdb ) ) . ' )</span></a>';

		return $views;
	}

	/**
	 * Render the transaction ID column.
	 *
	 * @param array $item Row data array.
	 *
	 * @return string
	 */
	public function column_id_tw_transactions( $item ) {
		return esc_attr( $item['id_tw_transactions'] );
	}

	/**
	 * Render the output for a given column key.
	 *
	 * @param array  $item Row data array.
	 * @param string $column_name Column being rendered.
	 *
	 * @return string
	 */
	public function column_default( array $item, string $column_name ) {
		$column = '';

		switch ( $column_name ) {
			case 'id_tw_transactions':
			case 'customer_name':
			case 'transactionId':
			case 'status':
			case 'checkout_url':
				$column = $item[ $column_name ];
				break;
			case 'id_cart':
				$column = '#' . $item[ $column_name ];
		}

		return $column;
	}

	/**
	 * Render row checkbox for bulk actions.
	 *
	 * @param array $item Row data array.
	 *
	 * @return string
	 */
	public function column_cb( array $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			esc_attr( $this->_args['singular'] ),
			esc_attr( $item['id_tw_transactions'] )
		);
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since 3.1.0
	 * @access public
	 * @abstract
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'                 => '<input type="checkbox" />',
			'id_tw_transactions' => esc_html__( 'ID', 'xmoney-payments' ),
			'id_cart'            => esc_html__( 'Order reference', 'xmoney-payments' ),
			'customer_name'      => esc_html__( 'Customer name', 'xmoney-payments' ),
			'transactionId'      => esc_html__( 'Transaction ID', 'xmoney-payments' ),
			'status'             => esc_html__( 'Status', 'xmoney-payments' ),
			'checkout_url'       => esc_html__( 'Checkout url', 'xmoney-payments' ),
		);
	}

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		$sortable_columns = array(
			'id_tw_transactions' => array( 'id_tw_transactions', false ),
			'customer_name'      => array( 'customer_name', false ),
			'transactionId'      => array( 'transactionId', false ),
			'status'             => array( 'status', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Prepare the table items and pagination.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		// Sanitize GET values (read-only, no nonce required)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- All GET accesses are for read-only listing controls.
		$s = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : 'all';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only sort key.
		$order_by = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only sort direction.
		$order_how = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'asc';

		// Table name – safe and prefixed
		$transaction_table = esc_sql( $wpdb->prefix . 'xmoney_payments_transactions' );

		$per_page  = 10;
		$where_sql = '';

		// WHERE clause
		if ( 'all' !== $s ) {
			$search_like = '%' . $wpdb->esc_like( $s ) . '%';
			$where_sql   = $wpdb->prepare( 'WHERE tr.id_cart LIKE %s', $search_like );
		}

		// Allowed ordering fields
		$allowed_orderby = array( 'id_tw_transactions', 'transactionId', 'status', 'id_cart' );

		if ( in_array( $order_by, $allowed_orderby, true ) ) {
			$order_how = strtolower( $order_how ) === 'desc' ? 'DESC' : 'ASC';
			$order_sql = "ORDER BY tr.{$order_by} {$order_how}";
		} else {
			// Default sorting
			$order_sql = 'ORDER BY tr.id_tw_transactions DESC';
		}

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		// Select transactions ONLY (no user table)
		$query = "
        SELECT
            tr.id_tw_transactions,
            tr.id_cart,
            tr.identifier,
            tr.transactionId,
            tr.orderId,
            tr.status,
            tr.checkout_url
        FROM {$transaction_table} tr
        {$where_sql}
        {$order_sql}
    ";

        // phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query, // Safe dynamic SQL
            ARRAY_A
		);
        // phpcs:enable

		// Add customer names safely (VIP-friendly)
		$data = array();

		foreach ( $rows as $row ) {
			$customer_id = 0;

			// Example identifier: "site123_user_55"
			if ( ! empty( $row['identifier'] ) ) {
				preg_match( '/_user_([0-9]+)/', $row['identifier'], $matches );
				if ( ! empty( $matches[1] ) ) {
					$customer_id = (int) $matches[1];
				}
			}

			// Load user safely
			$user = $customer_id ? get_user_by( 'ID', $customer_id ) : false;

			$row['customer_name'] = $user ? $user->display_name : __( 'Guest', 'xmoney-payments' );

			$data[] = $row;
		}

		// Pagination
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->items = array_slice( $data, ( $current_page - 1 ) * $per_page, $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}
