<?php
/**
 * Comment API: Walker_Comment class
 *
 * @package ClassicPress
 * @subpackage Comments
 * @since 4.4.0
 */

/**
 * Core walker class used to create an HTML list of comments.
 *
 * @since 2.7.0
 * @since CP-2.0.0 Default to HTML5. Removed obsolete markup function.
 *
 * @see Walker
 */
class Walker_Comment extends Walker {

	/**
	 * What the class handles.
	 *
	 * @since 2.7.0
	 * @var string
	 *
	 * @see Walker::$tree_type
	 */
	public $tree_type = 'comment';

	/**
	 * Database fields to use.
	 *
	 * @since 2.7.0
	 * @var string[]
	 *
	 * @see Walker::$db_fields
	 * @todo Decouple this
	 */
	public $db_fields = array(
		'parent' => 'comment_parent',
		'id'     => 'comment_ID',
	);

	/**
	 * Starts the list before the elements are added.
	 *
	 * @since 2.7.0
	 *
	 * @see Walker::start_lvl()
	 * @global int $comment_depth
	 *
	 * @param string $output Used to append additional content (passed by reference).
	 * @param int    $depth  Optional. Depth of the current comment. Default 0.
	 * @param array  $args   Optional. Uses 'style' argument for type of HTML list. Default empty array.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1;

		switch ( $args['style'] ) {
			case 'div':
				break;
			case 'ol':
				$output .= '<ol class="children">' . "\n";
				break;
			case 'ul':
			default:
				$output .= '<ul class="children">' . "\n";
				break;
		}
	}

	/**
	 * Ends the list of items after the elements are added.
	 *
	 * @since 2.7.0
	 *
	 * @see Walker::end_lvl()
	 * @global int $comment_depth
	 *
	 * @param string $output Used to append additional content (passed by reference).
	 * @param int    $depth  Optional. Depth of the current comment. Default 0.
	 * @param array  $args   Optional. Will only append content if style argument value is 'ol' or 'ul'.
	 *                       Default empty array.
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1;

		switch ( $args['style'] ) {
			case 'div':
				break;
			case 'ol':
				$output .= "</ol><!-- .children -->\n";
				break;
			case 'ul':
			default:
				$output .= "</ul><!-- .children -->\n";
				break;
		}
	}

	/**
	 * Traverses elements to create list from elements.
	 *
	 * This function is designed to enhance Walker::display_element() to
	 * display children of higher nesting levels than selected inline on
	 * the highest depth level displayed. This prevents them being orphaned
	 * at the end of the comment list.
	 *
	 * Example: max_depth = 2, with 5 levels of nested content.
	 *     1
	 *      1.1
	 *        1.1.1
	 *        1.1.1.1
	 *        1.1.1.1.1
	 *        1.1.2
	 *        1.1.2.1
	 *     2
	 *      2.2
	 *
	 * @since 2.7.0
	 *
	 * @see Walker::display_element()
	 * @see wp_list_comments()
	 *
	 * @param WP_Comment $element           Comment data object.
	 * @param array      $children_elements List of elements to continue traversing. Passed by reference.
	 * @param int        $max_depth         Max depth to traverse.
	 * @param int        $depth             Depth of the current element.
	 * @param array      $args              An array of arguments.
	 * @param string     $output            Used to append additional content. Passed by reference.
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
		if ( ! $element ) {
			return;
		}

		$id_field = $this->db_fields['id'];
		$id       = $element->$id_field;

		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );

		/*
		 * If at the max depth, and the current element still has children, loop over those
		 * and display them at this level. This is to prevent them being orphaned to the end
		 * of the list.
		 */
		if ( $max_depth <= $depth + 1 && isset( $children_elements[ $id ] ) ) {
			foreach ( $children_elements[ $id ] as $child ) {
				$this->display_element( $child, $children_elements, $max_depth, $depth, $args, $output );
			}

			unset( $children_elements[ $id ] );
		}
	}

	/**
	 * Starts the element output.
	 *
	 * @since 2.7.0
	 * @since 5.9.0 Renamed `$comment` to `$data_object` and `$id` to `$current_object_id`
	 *              to match parent class for PHP 8 named parameter support.
	 * @since CP-2.0.0 Default to 'html' output for comments.
	 *
	 * @see Walker::start_el()
	 * @see wp_list_comments()
	 * @global int        $comment_depth
	 * @global WP_Comment $comment       Global comment object.
	 *
	 * @param string     $output            Used to append additional content. Passed by reference.
	 * @param WP_Comment $data_object       Comment data object.
	 * @param int        $depth             Optional. Depth of the current comment in reference to parents. Default 0.
	 * @param array      $args              Optional. An array of arguments. Default empty array.
	 * @param int        $current_object_id Optional. ID of the current comment. Default 0.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = array(), $current_object_id = 0 ) {
		// Restores the more descriptive, specific name for use within this method.
		$comment = $data_object;

		$depth++;
		$GLOBALS['comment_depth'] = $depth;
		$GLOBALS['comment']       = $comment;

		if ( ! empty( $args['callback'] ) ) {
			ob_start();
			call_user_func( $args['callback'], $comment, $args, $depth );
			$output .= ob_get_clean();
			return;
		}

		if ( 'comment' === $comment->comment_type ) {
			add_filter( 'comment_text', array( $this, 'filter_comment_text' ), 40, 2 );
		}

		if ( ( 'pingback' === $comment->comment_type || 'trackback' === $comment->comment_type ) && $args['short_ping'] ) {
			ob_start();
			$this->ping( $comment, $depth, $args );
			$output .= ob_get_clean();
		} else {
			ob_start();
			$this->html5_comment( $comment, $depth, $args );
			$output .= ob_get_clean();
		}

		if ( 'comment' === $comment->comment_type ) {
			remove_filter( 'comment_text', array( $this, 'filter_comment_text' ), 40 );
		}
	}

	/**
	 * Ends the element output, if needed.
	 *
	 * @since 2.7.0
	 * @since 5.9.0 Renamed `$comment` to `$data_object` to match parent class for PHP 8 named parameter support.
	 *
	 * @see Walker::end_el()
	 * @see wp_list_comments()
	 *
	 * @param string     $output      Used to append additional content. Passed by reference.
	 * @param WP_Comment $data_object Comment data object.
	 * @param int        $depth       Optional. Depth of the current comment. Default 0.
	 * @param array      $args        Optional. An array of arguments. Default empty array.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = array() ) {
		if ( ! empty( $args['end-callback'] ) ) {
			ob_start();
			call_user_func(
				$args['end-callback'],
				$data_object, // The current comment object.
				$args,
				$depth
			);
			$output .= ob_get_clean();
			return;
		}
		if ( 'div' === $args['style'] ) {
			$output .= "</div><!-- #comment-## -->\n";
		} else {
			$output .= "</li><!-- #comment-## -->\n";
		}
	}

	/**
	 * Outputs a pingback comment.
	 *
	 * @since 3.6.0
	 *
	 * @see wp_list_comments()
	 *
	 * @param WP_Comment $comment The comment object.
	 * @param int        $depth   Depth of the current comment.
	 * @param array      $args    An array of arguments.
	 */
	protected function ping( $comment, $depth, $args ) {
		$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';
		?>
		<<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( '', $comment ); ?>>
			<div class="comment-body">
				<?php _e( 'Pingback:' ); ?> <?php comment_author_link( $comment ); ?> <?php edit_comment_link( __( 'Edit' ), '<span class="edit-link">', '</span>' ); ?>
			</div>
		<?php
	}

	/**
	 * Filters the comment text.
	 *
	 * Removes links from the pending comment's text if the commenter did not consent
	 * to the comment cookies.
	 *
	 * @since 5.4.2
	 *
	 * @param string          $comment_text Text of the current comment.
	 * @param WP_Comment|null $comment      The comment object. Null if not found.
	 * @return string Filtered text of the current comment.
	 */
	public function filter_comment_text( $comment_text, $comment ) {
		$commenter          = wp_get_current_commenter();
		$show_pending_links = ! empty( $commenter['comment_author'] );

		if ( $comment && '0' == $comment->comment_approved && ! $show_pending_links ) {
			$comment_text = wp_kses( $comment_text, array() );
		}

		return $comment_text;
	}

	/**
	 * Outputs a comment in the HTML5 format.
	 *
	 * @since 3.6.0
	 * @since CP-2.0.0 Comment author and metadata are moved to template functions and called via hooks.
	 *
	 * @see wp_list_comments()
	 *
	 * @param WP_Comment $comment Comment to display.
	 * @param int        $depth   Depth of the current comment.
	 * @param array      $args    An array of arguments.
	 */
	protected function html5_comment( $comment, $depth, $args ) {
		$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';

		$commenter          = wp_get_current_commenter();
		$show_pending_links = ! empty( $commenter['comment_author'] );

		if ( $commenter['comment_author_email'] ) {
			$moderation_note = __( 'Your comment is awaiting moderation.' );
		} else {
			$moderation_note = __( 'Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.' );
		}
		?>
		<<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( $this->has_children ? 'parent' : '', $comment ); ?>>
			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
				<footer class="comment-meta">
					<div class="comment-author vcard">
					<?php
						/**
						 * Hook for including comment author data.
						 * Default action `display_comment_author_data` in wp-includes/comment-template.php includes comment author's name and avatar.
						 *
						 * @since CP-2.0.0
						 */
						do_action( 'comment_author_data', $comment, $args, $show_pending_links );
					?>
					</div><!-- .comment-author -->

					<div class="comment-metadata">
					<?php
						/**
						 * Hook for including comment metadata.
						 * Default action `display_comment_metadata` in wp-includes/comment-template.php includes a comment's date and an edit link.
						 *
						 * @since CP-2.0.0
						 */
						do_action( 'comment_metadata', $comment, $args );
					?>
					</div><!-- .comment-metadata -->

					<?php if ( '0' == $comment->comment_approved ) : ?>
					<em class="comment-awaiting-moderation"><?php echo $moderation_note; ?></em>
					<?php endif; ?>
				</footer><!-- .comment-meta -->

				<div class="comment-content">
					<?php comment_text(); ?>
				</div><!-- .comment-content -->

				<?php
				if ( '1' == $comment->comment_approved || $show_pending_links ) {
					comment_reply_link(
						array_merge(
							$args,
							array(
								'add_below' => 'div-comment',
								'depth'     => $depth,
								'max_depth' => $args['max_depth'],
								'before'    => '<div class="reply">',
								'after'     => '</div>',
							)
						)
					);
				}
				?>
			</article><!-- .comment-body -->
		<?php
	}
}
