<?php
/**
 * List Table API: CP_Post_Revisions_List_Table class
 *
 * @package ClassicPress
 * @subpackage Administration
 * @since CP-2.6.0
 */

/**
 * Core class used to implement displaying revisions in a list table.
 *
 * @since CP-2.6.0
 *
 * @see WP_List_Table
 */
class CP_Post_Revisions_List_Table extends WP_List_Table {

	/**
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'      => '<input type="checkbox">',
			'id'      => __( 'Revision ID' ),
			'author'  => __( 'Author' ),
			'revised' => __( 'Revised by' ),
			'date'    => __( 'Date of Revision' ),
			'view'    => __( 'View in Modal' ),
			'delete'  => __( 'Delete' ),
		);
	}

	/**
	 * Renders the list of revisions
	 *
	 * @since CP-2.6.0
	 *
	 * @return string
	 */
	public function prepare_items() {
		$post_id = absint( $_GET['post_parent'] );
		$revisions = wp_get_post_revisions( $post_id );

		// Create an empty array
		$safe_revisions = array();

		// Find the key for the last revision
		$last_revision = reset( $revisions );

		// Fill the array with all the revisions except the most recent (to prevent its deletion)
		foreach ( $revisions as $key => $revision ) {
			if ( $revision->ID !== $last_revision->ID ) {
				$safe_revisions[] = $revision;
			}
		}
		$this->items = $safe_revisions;
	}

	/**
	 * Renders the bulk delete checkbox
	 *
	 * @since CP-2.6.0
	 *
	 * @param WP_Post $item The current revision object.
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<label class="screen-reader-text" for="revision_%1$d">' . __( 'Select Revision %1$d' ) . '</label>' .
			'<input type="checkbox" name="revision_ids[]" id="revision_%1$d" value="%1$d">',
			absint( $item->ID )
		);
	}

	/**
	 * Handles output for the default column.
	 *
	 * @since CP-2.6.0
	 *
	 * @param WP_Post $item        The current revision object.
	 * @param string  $column_name Current column name.
	 */
	public function column_default( $item, $column_name ) {
		$post_id = absint( $_GET['post_parent'] );
		$post = get_post( $post_id );

		switch ( $column_name ) {
			case 'id':
				return '<span>' . absint( $item->ID ) . '</span>';
			case 'author':
				return esc_html( get_the_author_meta( 'display_name', $post->post_author ) );
			case 'revised':
				return esc_html( get_the_author_meta( 'display_name', $item->post_author ) );
			case 'date':
				return date( 'j F Y H:i:s', strtotime( $item->post_date ) );
			case 'view':
				return '<button type="button" class="page-title-action">' . __( 'View' ) . '</button>';
			case 'delete':
				$delete_url = add_query_arg(
					array(
						'action'      => 'delete',
						'revision_id' => $item->ID,
						'post_parent' => $post_id,
					),
					admin_url( 'revisions-list.php' )
				);
				$nonce_url = wp_nonce_url( $delete_url, 'delete_revision_' . $item->ID );
				return '<a href="' . esc_url( $nonce_url ) . '">' . __( 'Delete' ) . '</a>';
			default:
				return '';
		}
	}

	/**
	 * Retrieves an associative array of bulk actions available on this table.
	 *
	 * @since CP-2.6.0
	 *
	 * @return array Array of bulk action labels keyed by their action.
	 */
	public function get_bulk_actions() {
		return array(
			'bulk-delete' => __( 'Delete' ),
		);
	}

	/**
	 * Processes the bulk action
	 *
	 * @since CP-2.6.0
	 */
	public function process_bulk_action() {
		$action = $this->current_action();
		if ( $action ) {
			switch ( $action ) {
				case 'bulk-delete':
					if ( empty( $_GET['revision_ids'] ) ) {
						return;
						break;
					}
					$revision_ids = array_map( 'absint', $_GET['revision_ids'] );
					foreach ( $revision_ids as $revision_id ) {
						wp_delete_post_revision( $revision_id );
					}
					break;
				default:
					return;
					break;
			}
		}
	}

	/**
	 * Adds a modal at the bottom of the page
	 *
	 * @since CP-2.6.0
	 *
	 * @param string $which bottom
	 *
	 * @return string
	 */
	public function extra_tablenav( $which ) {
		if ( $which === 'bottom' ) {
			?>
			
			<dialog id="modal-revision" class="modal-revision" aria-labelledby="modal-revision-title">

				<header class="modal-revision-header">
					<h2 id="modal-revision-title"></h2>
					<button type="button" id="modal-revision-close-button" class="dashicons-no dashicons" autofocus>
						<span class="screen-reader-text"><?php esc_html_e( 'Close dialog' ); ?></span>
					</button>
				</header>

				<div class="modal-revision-content-outer">
					<div id="modal-revision-content-inner" class="modal-revision-content"></div>

					<footer class="modal-revision-footer">
						<button type="button" id="modal-revision-button" class="button button-secondary"><?php esc_html_e( 'Close' ); ?></button>
					</footer>
				</div>

			</dialog>
			
			<?php
		}
	}
}
