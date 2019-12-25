<?php
/**
 * The main plugin class Mx_Recent_Posts_CPT_Addon_FB
 *
 * @since 1.2
 * @package Recent Posts CPT Fusion Builder Addon
 */

/**
 * The main plugin class.
 */
class Mx_Recent_Posts_CPT_Addon_FB {

	/**
	 * Recent Posts element counter.
	 *
	 * @access private
	 * @since 1.5.2
	 * @var int
	 */
	private $recent_posts_counter = 1;

	/**
	 * An array of the shortcode arguments.
	 *
	 * @access protected
	 * @since 1.0
	 * @var array
	 */
	protected $args;

	/**
	 * An array of meta settings.
	 *
	 * @access private
	 * @since 1.0
	 * @var array
	 */
	private $meta_info_settings = [];

	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @access private
	 * @since 1.0
	 * @var object
	 */
	private static $instance;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 */
	public static function get_instance() {

		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null === self::$instance ) {
			self::$instance = new Mx_Recent_Posts_CPT_Addon_FB();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {

		add_filter( 'fusion_attr_recentposts-shortcode', [ $this, 'attr' ] );
		add_filter( 'fusion_attr_recentposts-shortcode-section', [ $this, 'section_attr' ] );
		add_filter( 'fusion_attr_recentposts-shortcode-column', [ $this, 'column_attr' ] );
		add_filter( 'fusion_attr_recentposts-shortcode-content', [ $this, 'content_attr' ] );
		add_filter( 'fusion_attr_recentposts-shortcode-slideshow', [ $this, 'slideshow_attr' ] );
		add_filter( 'fusion_attr_recentposts-shortcode-img', [ $this, 'img_attr' ] );
		add_filter( 'fusion_attr_recentposts-shortcode-img-link', [ $this, 'link_attr' ] );

		add_shortcode( 'mx_fusion_recent_posts_cpt', array( $this, 'render' ) );

		// Ajax mechanism for query related part.
		add_action( 'wp_ajax_get_mx_fusion_recent_posts_cpt', [ $this, 'ajax_query' ] );

	}

	/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function get_element_defaults() {

				$fusion_settings = fusion_get_fusion_settings();

				return [
					'hide_on_mobile'      => fusion_builder_default_visibility( 'string' ),
					'class'               => '',
					'id'                  => '',
					'pull_by'             => '',
					'post_type_cpt'       => 'post',					
					'cat_id'              => '',
					'cat_slug'            => '',
					'tag_slug'            => '',
					'exclude_tags'        => '',
					'columns'             => 3,
					'content_alignment'   => '',
					'excerpt'             => 'no',
					'exclude_cats'        => '',
					'excerpt_length'      => '',
					'excerpt_words'       => '15', // Deprecated.
					'hover_type'          => 'none',
					'layout'              => 'default',
					'meta'                => 'yes',
					'meta_author'         => 'no',
					'meta_categories'     => 'no',
					'meta_comments'       => 'yes',
					'meta_date'           => 'yes',
					'meta_tags'           => 'no',
					'number_posts'        => '4',
					'offset'              => '',
					'picture_size'        => 'fixed',
					'post_status'         => '',
					'scrolling'           => 'no',
					'strip_html'          => 'yes',
					'title'               => 'yes',
					'thumbnail'           => 'yes',
					'animation_direction' => 'left',
					'animation_speed'     => '',
					'animation_type'      => '',
					'animation_offset'    => $fusion_settings->get( 'animation_offset' ),
				];
			}

			/**
			 * Maps settings to param variables.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function settings_to_params() {
				return [
					'animation_offset' => 'animation_offset',
				];
			}

			/**
			 * Used to set any other variables for use on front-end editor template.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function get_element_extras() {
				$fusion_settings = fusion_get_fusion_settings();
				return [
					'disable_date_rich_snippet_pages'   => $fusion_settings->get( 'disable_date_rich_snippet_pages' ),
					'pagination_range_global'           => apply_filters( 'fusion_pagination_size', $fusion_settings->get( 'pagination_range' ) ),
					'pagination_start_end_range_global' => apply_filters( 'fusion_pagination_start_end_size', $fusion_settings->get( 'pagination_start_end_range' ) ),
				];
			}

			/**
			 * Maps settings to extra variables.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function settings_to_extras() {

				return [
					'disable_date_rich_snippet_pages' => 'disable_date_rich_snippet_pages',
					'pagination_range'                => 'pagination_range_global',
					'pagination_start_end_range'      => 'pagination_start_end_range_global',
				];
			}

			/**
			 * Gets the query data.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @param array $defaults An array of defaults.
			 * @return void
			 */
			public function ajax_query( $defaults ) {
				check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );
				$this->query( $defaults );
			}

			/**
			 * Gets the query data.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @param array $defaults The defaults array.
			 * @return array
			 */
			public function query( $defaults ) {
				$fusion_settings = fusion_get_fusion_settings();

				$live_request = false;

				// From Ajax Request.
				if ( isset( $_POST['model'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$defaults     = wp_unslash( $_POST['model']['params'] ); // phpcs:ignore WordPress.Security
					$defaults     = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $defaults, 'mx_fusion_recent_posts_cpt' );
					$live_request = true;
				}				

				$defaults['offset']         = ( '0' === $defaults['offset'] ) ? '' : $defaults['offset'];
				$defaults['columns']        = min( $defaults['columns'], 6 );
				$defaults['strip_html']     = ( 'yes' === $defaults['strip_html'] || 'true' === $defaults['strip_html'] ) ? true : false;
				$defaults['posts_per_page'] = ( $defaults['number_posts'] ) ? $defaults['number_posts'] : $defaults['posts_per_page'];
				$defaults['scrolling']      = ( '-1' === $defaults['number_posts'] ) ? 'no' : $defaults['scrolling'];

				if ( $defaults['excerpt_length'] || '0' === $defaults['excerpt_length'] ) {
					$defaults['excerpt_words'] = $defaults['excerpt_length'];
				}
				if ( 'tag' !== $defaults['pull_by'] ) {
					// Check for cats to exclude; needs to be checked via exclude_cats param
					// and '-' prefixed cats on cats param, exclution via exclude_cats param.
					$cats_to_exclude = explode( ',', $defaults['exclude_cats'] );
					if ( $cats_to_exclude ) {
						foreach ( $cats_to_exclude as $cat_to_exclude ) {
							$id_obj = get_category_by_slug( $cat_to_exclude );
							if ( $id_obj ) {
								$cats_id_to_exclude[] = $id_obj->term_id;
							}
						}
						if ( isset( $cats_id_to_exclude ) && $cats_id_to_exclude ) {
							$defaults['category__not_in'] = $cats_id_to_exclude;
						}
					}

					// Setting up cats to be used and exclution using '-' prefix on cats param; transform slugs to ids.
					$cat_ids    = '';
					$categories = explode( ',', $defaults['cat_slug'] );
					if ( isset( $categories ) && $categories ) {
						foreach ( $categories as $category ) {
							if ( $category ) {
								$cat_obj = get_category_by_slug( $category );
								if ( isset( $cat_obj->term_id ) ) {
									$cat_ids .= ( 0 === strpos( $category, '-' ) ) ? '-' . $cat_obj->cat_ID . ',' : $cat_obj->cat_ID . ',';
								}
							}
						}
					}
					$defaults['cat'] = substr( $cat_ids, 0, -1 ) . $defaults['cat_id'];
				} else {
					// Check for tags to exclude; needs to be checked via exclude_tags param
					// and '-' prefixed tags on tags param exclusion via exclude_tags param.
					$tags_to_exclude    = explode( ',', $defaults['exclude_tags'] );
					$tags_id_to_exclude = [];
					if ( $tags_to_exclude ) {
						foreach ( $tags_to_exclude as $tag_to_exclude ) {
							$id_obj = get_term_by( 'slug', $tag_to_exclude, 'post_tag' );
							if ( $id_obj ) {
								$tags_id_to_exclude[] = $id_obj->term_id;
							}
						}
						if ( $tags_id_to_exclude ) {
							$defaults['tag__not_in'] = $tags_id_to_exclude;
						}
					}

					// Setting up tags to be used.
					$tag_ids = [];
					if ( '' !== $defaults['tag_slug'] ) {
						$tags = explode( ',', $defaults['tag_slug'] );
						if ( isset( $tags ) && $tags ) {
							foreach ( $tags as $tag ) {
								$id_obj = get_term_by( 'slug', $tag, 'post_tag' );

								if ( $id_obj ) {
									$tag_ids[] = $id_obj->term_id;
								}
							}
						}
					}
					$defaults['tag__in'] = $tag_ids;
				}

				$args = [
					'posts_per_page'      => $defaults['number_posts'],
					'ignore_sticky_posts' => 1,
					'post_type' => $defaults['post_type_cpt']
				];

				// Check if there is paged content.
				$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
				if ( is_front_page() ) {
					$paged = ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1;
				}
				$args['paged'] = $paged;

				if ( $defaults['offset'] ) {
					$args['offset'] = $defaults['offset'] + ( $paged - 1 ) * $defaults['number_posts'];
				}

				if ( isset( $defaults['cat'] ) && $defaults['cat'] ) {
					$args['cat'] = $defaults['cat'];
				}

				if ( isset( $defaults['category__not_in'] ) && is_array( $defaults['category__not_in'] ) ) {
					$args['category__not_in'] = $defaults['category__not_in'];
				}

				if ( isset( $defaults['tag__in'] ) && $defaults['tag__in'] ) {
					$args['tag__in'] = $defaults['tag__in'];
				}

				if ( isset( $defaults['tag__not_in'] ) && is_array( $defaults['tag__not_in'] ) ) {
					$args['tag__not_in'] = $defaults['tag__not_in'];
				}

				if ( '' !== $defaults['post_status'] ) {
					$args['post_status'] = explode( ',', $defaults['post_status'] );
				}

				$recent_posts = fusion_cached_query( $args );

				$this->args['max_num_pages'] = $recent_posts->max_num_pages;

				if ( ! $live_request ) {
					return $recent_posts;
				}

				// If we are here it means its a live request for builder so we put together package of data.
				if ( ! $recent_posts->have_posts() ) {
					$return_data['placeholder'] = fusion_builder_placeholder( 'post', 'blog posts' );
					echo wp_json_encode( $return_data );
					wp_die();
				}

				while ( $recent_posts->have_posts() ) {
					$recent_posts->the_post();
					$image_sizes = [ 'full', 'recent-posts', 'portfolio-five' ];

					// Get image for standard thumbnail if set.
					$thumbnail = false;
					if ( has_post_thumbnail() ) {
						$thumbnail_id = get_post_thumbnail_id();

						// Get all image sizes, not just 1.
						foreach ( $image_sizes as $image_size ) {

							// Responsive images.
							if ( 'full' === $image_size ) {
								fusion_library()->get_images_obj()->set_grid_image_meta(
									[
										'layout'       => 'grid',
										'columns'      => $defaults['columns'],
										'gutter_width' => '30',
									]
								);

								$attachment_image = wp_get_attachment_image( $thumbnail_id, $image_size );
								$attachment_image = fusion_library()->get_images_obj()->edit_grid_image_src( $attachment_image, null, $thumbnail_id, 'full' );

								fusion_library()->get_images_obj()->set_grid_image_meta( [] );

							} else {
								$attachment_image = wp_get_attachment_image( $thumbnail_id, $image_size );
							}

							$thumbnail[ $image_size ] = $attachment_image;
						}
					}

					// Get array of featured images if set.
					$multiple_featured_images = false;
					$i                        = 2;
					$posts_slideshow_number   = $fusion_settings->get( 'posts_slideshow_number' );
					if ( '' === $posts_slideshow_number ) {
						$posts_slideshow_number = 5;
					}
					while ( $i <= $posts_slideshow_number ) {

						$attachment_new_id = false;

						if ( function_exists( 'fusion_get_featured_image_id' ) && fusion_get_featured_image_id( 'featured-image-' . $i, 'post' ) ) {
							$attachment_new_id = fusion_get_featured_image_id( 'featured-image-' . $i, 'post' );
						}

						if ( $attachment_new_id ) {

							// Get all image sizes, not just 1.
							foreach ( $image_sizes as $image_size ) {

								// Responsive images.
								if ( 'full' === $image_size ) {
									fusion_library()->get_images_obj()->set_grid_image_meta(
										[
											'layout'       => 'grid',
											'columns'      => $defaults['columns'],
											'gutter_width' => '30',
										]
									);

									$attachment_image = wp_get_attachment_image( $attachment_new_id, $image_size );

									$attachment_image = fusion_library()->get_images_obj()->edit_grid_image_src( $attachment_image, null, $attachment_new_id, 'full' );

									fusion_library()->get_images_obj()->set_grid_image_meta( [] );
								} else {
									$attachment_image = wp_get_attachment_image( $attachment_new_id, $image_size );
								}

								$multiple_featured_images[ $attachment_new_id ][ $image_size ] = $attachment_image;
							}
						}

						$i++;
					}

					// Rich snippets for both title options.
					$rich_snippets = [
						'yes' => fusion_builder_render_rich_snippets_for_pages( false ),
						'no'  => fusion_builder_render_rich_snippets_for_pages(),
					];

					// Comments Link.
					ob_start();
					comments_popup_link( esc_attr__( '0 Comments', 'fusion-builder' ), esc_attr__( '1 Comment', 'fusion-builder' ), esc_attr__( '% Comments', 'fusion-builder' ) );
					$comments_link = ob_get_contents();
					ob_get_clean();

					// Contents, strip tags on and off.
					$content = fusion_get_content_data( 'mx_fusion_recent_posts_cpt', true );

					$post_id = get_the_ID();

					$return_data['max_num_pages'] = $recent_posts->max_num_pages;
					$return_data['posts'][]       = [
						'format'                           => get_post_format(),
						'alternate_date_format_day'        => get_the_time( $fusion_settings->get( 'alternate_date_format_day' ) ),
						'alternate_date_format_month_year' => get_the_time( $fusion_settings->get( 'alternate_date_format_month_year' ) ),
						'thumbnail'                        => $thumbnail,
						'password_required'                => post_password_required( $post_id ),
						'video'                            => apply_filters( 'fusion_builder_post_video', $post_id ),
						'multiple_featured_images'         => $multiple_featured_images,
						'title'                            => get_the_title(),
						'rich_snippet'                     => $rich_snippets,
						'permalink'                        => get_permalink( $post_id ),
						'comments_link'                    => $comments_link,
						'date_format'                      => get_the_time( $fusion_settings->get( 'date_format' ), $post_id ),
						'meta_data'                        => fusion_get_meta_data( $post_id ),
						'content'                          => $content,
					];
				}
				echo wp_json_encode( $return_data );
				wp_die();
			}

	/**
	 * Add new radio_image setting field to Fusion Builder.
	 *
	 * @access public
	 * @since 1.1
	 * @param array $fields The array of fields added with filter.
	 * @return array
	 */
	public function render( $args, $content = '' ) {
		
		global $fusion_settings;

		$defaults = FusionBuilder::set_shortcode_defaults( self::get_element_defaults(), $args, 'mx_fusion_recent_posts_cpt' );

		$defaults['offset']         = ( '0' === $defaults['offset'] ) ? '' : $defaults['offset'];
		$defaults['columns']        = min( $defaults['columns'], 6 );
		$defaults['strip_html']     = ( 'yes' === $defaults['strip_html'] || 'true' === $defaults['strip_html'] ) ? true : false;
		$defaults['posts_per_page'] = ( $defaults['number_posts'] ) ? $defaults['number_posts'] : $defaults['posts_per_page'];
		$defaults['scrolling']      = ( '-1' === $defaults['number_posts'] ) ? 'no' : $defaults['scrolling'];

		if ( $defaults['excerpt_length'] || '0' === $defaults['excerpt_length'] ) {
			$defaults['excerpt_words'] = $defaults['excerpt_length'];
		}

		extract( $defaults );

		// Deprecated 5.2.1 hide value, mapped to no.
		if ( 'hide' === $excerpt ) {
			$excerpt = 'no';
		}

		$defaults['meta_author']     = ( 'yes' === $defaults['meta_author'] );
		$defaults['meta_categories'] = ( 'yes' === $defaults['meta_categories'] );
		$defaults['meta_comments']   = ( 'yes' === $defaults['meta_comments'] );
		$defaults['meta_date']       = ( 'yes' === $defaults['meta_date'] );
		$defaults['meta_tags']       = ( 'yes' === $defaults['meta_tags'] );
		$defaults['post_type_cpt'] 	 = $defaults['post_type_cpt'];

		// Set the meta info settings for later use.
		$this->meta_info_settings['post_meta']          = $defaults['meta'];
		$this->meta_info_settings['post_meta_author']   = $defaults['meta_author'];
		$this->meta_info_settings['post_meta_date']     = $defaults['meta_date'];
		$this->meta_info_settings['post_meta_cats']     = $defaults['meta_categories'];
		$this->meta_info_settings['post_meta_tags']     = $defaults['meta_tags'];
		$this->meta_info_settings['post_meta_comments'] = $defaults['meta_comments'];

		$this->args   = $defaults;
		$recent_posts = $this->query( $defaults );
		$items        = '';

		if ( ! $recent_posts->have_posts() ) {
			return fusion_builder_placeholder( 'post', 'blog posts' );
		}

		while ( $recent_posts->have_posts() ) {
			$recent_posts->the_post();

			$attachment = $date_box = $slideshow = $slides = $content = '';

			$permalink = get_permalink( get_the_ID() );
			if ( 'private' === get_post_status() && ! is_user_logged_in() || in_array( get_post_status(), [ 'pending', 'draft', 'future' ], true ) && ! current_user_can( 'edit-post' ) ) {
				$permalink = '#';
			}

			if ( 'date-on-side' === $layout ) {
				$post_format = get_post_format();

				switch ( $post_format ) {
					case 'gallery':
						$format_class = 'images';
						break;
					case 'link':
					case 'image':
						$format_class = $post_format;
						break;
					case 'quote':
						$format_class = 'quotes-left';
						break;
					case 'video':
						$format_class = 'film';
						break;
					case 'audio':
						$format_class = 'headphones';
						break;
					case 'chat':
						$format_class = 'bubbles';
						break;
					default:
						$format_class = 'pen';
						break;
				}

				$date_box = '<div ' . FusionBuilder::attributes( 'fusion-date-and-formats' ) . '><div ' . FusionBuilder::attributes( 'fusion-date-box updated' ) . '><span ' . FusionBuilder::attributes( 'fusion-date' ) . '>' . get_the_time( $fusion_settings->get( 'alternate_date_format_day' ) ) . '</span><span ' . FusionBuilder::attributes( 'fusion-month-year' ) . '>' . get_the_time( $fusion_settings->get( 'alternate_date_format_month_year' ) ) . '</span></div><div ' . FusionBuilder::attributes( 'fusion-format-box' ) . '><i ' . FusionBuilder::attributes( 'fusion-icon-' . $format_class ) . '></i></div></div>';
			}

			if ( 'yes' === $thumbnail && 'date-on-side' !== $layout && ! post_password_required( get_the_ID() ) ) {

				if ( 'auto' === $picture_size ) {
					$image_size = 'full';
				} elseif ( 'default' === $layout ) {
					$image_size = 'recent-posts';
				} elseif ( 'thumbnails-on-side' === $layout ) {
					$image_size = 'portfolio-five';
				}

				$post_video = apply_filters( 'fusion_builder_post_video', get_the_ID() );

				if ( has_post_thumbnail() || $post_video ) {
					if ( $post_video ) {
						$slides .= '<li><div ' . FusionBuilder::attributes( 'full-video' ) . '>' . $post_video . '</div></li>';
					}

					if ( has_post_thumbnail() ) {
						$thumbnail_id = get_post_thumbnail_id();

						// Responsive images.
						if ( 'full' === $image_size ) {
							fusion_library()->get_images_obj()->set_grid_image_meta(
								[
									'layout'       => 'grid',
									'columns'      => $columns,
									'gutter_width' => '30',
								]
							);

							$attachment_image = wp_get_attachment_image( $thumbnail_id, $image_size );
							$attachment_image = fusion_library()->get_images_obj()->edit_grid_image_src( $attachment_image, null, $thumbnail_id, 'full' );

							fusion_library()->get_images_obj()->set_grid_image_meta( [] );
						} else {
							$attachment_image = wp_get_attachment_image( $thumbnail_id, $image_size );
						}

						$slides .= '<li><a href="' . esc_url( $permalink ) . '" ' . FusionBuilder::attributes( 'recentposts-shortcode-img-link' ) . '>' . $attachment_image . '</a></li>';
					}

					$i                      = 2;
					$posts_slideshow_number = $fusion_settings->get( 'posts_slideshow_number' );
					if ( '' === $posts_slideshow_number ) {
						$posts_slideshow_number = 5;
					}
					while ( $i <= $posts_slideshow_number ) {

						$attachment_new_id = false;

						if ( function_exists( 'fusion_get_featured_image_id' ) && fusion_get_featured_image_id( 'featured-image-' . $i, 'post' ) ) {
							$attachment_new_id = fusion_get_featured_image_id( 'featured-image-' . $i, 'post' );
						}

						if ( $attachment_new_id ) {

							// Responsive images.
							if ( 'full' === $image_size ) {
								fusion_library()->get_images_obj()->set_grid_image_meta(
									[
										'layout'  => 'grid',
										'columns' => $columns,
										'gutter_width' => '30',
									]
								);

								$attachment_image = wp_get_attachment_image( $attachment_new_id, $image_size );
								$attachment_image = fusion_library()->get_images_obj()->edit_grid_image_src( $attachment_image, null, $attachment_new_id, 'full' );

								fusion_library()->get_images_obj()->set_grid_image_meta( [] );
							} else {
								$attachment_image = wp_get_attachment_image( $attachment_new_id, $image_size );
							}

							$slides .= '<li><a href="' . esc_url( $permalink ) . '" ' . FusionBuilder::attributes( 'recentposts-shortcode-img-link' ) . '>' . $attachment_image . '</a></li>';
						}
						$i++;
					}

					$slideshow = '<div ' . FusionBuilder::attributes( 'recentposts-shortcode-slideshow' ) . '><ul ' . FusionBuilder::attributes( 'slides' ) . '>' . $slides . '</ul></div>';
				}
			}

			if ( 'yes' === $title ) {
				$content    .= ( function_exists( 'fusion_builder_render_rich_snippets_for_pages' ) ) ? fusion_builder_render_rich_snippets_for_pages( false ) : '';
				$entry_title = '';
				if ( $fusion_settings->get( 'disable_date_rich_snippet_pages' ) && $fusion_settings->get( 'disable_rich_snippet_title' ) ) {
					$entry_title = 'entry-title';
				}
				$content .= '<h4 class="' . $entry_title . '"><a href="' . esc_url( $permalink ) . '">' . get_the_title() . '</a></h4>';
			} else {
				$content .= fusion_builder_render_rich_snippets_for_pages();
			}

			if ( 'yes' === $meta ) {
				$meta_data = fusion_builder_render_post_metadata( 'recent_posts', $this->meta_info_settings );
				$content  .= '<p ' . FusionBuilder::attributes( 'meta' ) . '>' . $meta_data . '</p>';
			}

			if ( 'yes' === $excerpt ) {
				$content .= fusion_builder_get_post_content( '', 'yes', $excerpt_words, $strip_html );
			} elseif ( 'full' === $excerpt ) {
				$content .= fusion_builder_get_post_content( '', 'no', $excerpt_words, $strip_html );
			}

			$items .= '<article ' . FusionBuilder::attributes( 'recentposts-shortcode-column' ) . '>' . $date_box . $slideshow . '<div ' . FusionBuilder::attributes( 'recentposts-shortcode-content' ) . '>' . $content . '</div></article>';
		}

		// Pagination is used.
		$pagination = '';
		if ( 'no' !== $this->args['scrolling'] ) {
			$infinite_pagination = false;
			if ( 'pagination' !== $this->args['scrolling'] ) {
				$infinite_pagination = true;
			}

			$pagination = fusion_pagination( $recent_posts->max_num_pages, $fusion_settings->get( 'pagination_range' ), $recent_posts, $infinite_pagination, true );

			// If infinite scroll with "load more" button is used.
			if ( 'load_more_button' === $this->args['scrolling'] && 1 < $recent_posts->max_num_pages ) {
				$pagination .= '<div class="fusion-load-more-button fusion-blog-button fusion-clearfix">' . apply_filters( 'avada_load_more_posts_name', esc_attr__( 'Load More Posts', 'fusion-builder' ) ) . '</div>';
			}
		}

		$html = '<div ' . FusionBuilder::attributes( 'recentposts-shortcode' ) . '><section ' . FusionBuilder::attributes( 'recentposts-shortcode-section' ) . '>' . $items . '</section>' . $pagination . '</div>';

		wp_reset_postdata();

		$this->recent_posts_counter++;

		return $html;

	}

	/**
	 * Returns the content.
	 *
	 * @access public
	 * @since 1.0
	 * @param array  $atts    The attributes array.
	 * @param string $content The content.
	 * @return string
	 */
	public function fusion_quotes( $atts, $content ) {

		$unique_class = 'cbp-' . rand();
		$html = '<style type="text/css">';
		$html .= '.' . $unique_class . ' .cbp-qtprogress { background-color: ' . $atts['color_progress_bar'] . '; }';
		$html .= '.' . $unique_class . ' footer { color: ' . $atts['color_quote_title'] . '; }';
		$html .= '.' . $unique_class . ' .blockquote p { color: ' . $atts['color_quote_text'] . '; }';
		if ( isset( $atts['bg_pattern'] ) && '' !== $atts['bg_pattern'] ) {
			$html .= '.' . $unique_class . '.cbp-qtrotator { background: url(' . plugins_url( '\/img/' . $atts['bg_pattern'] . '.png', __FILE__ ) . '); }';
			$html .= '.' . $unique_class . '.cbp-qtrotator .cbp-qtcontent { padding-left: 15px; padding-right: 15px; }';
		}
		$html .= '</style>';
		$html .= '<div class="cbp-qtrotator ' . $unique_class . '">';
		$html .= do_shortcode( $content );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Returns the content.
	 *
	 * @access public
	 * @since 1.0
	 * @param array  $atts    The attributes array.
	 * @param string $content The content.
	 * @return string
	 */
	public function fusion_quote( $atts, $content ) {

		$html = '<div class="cbp-qtcontent">';
		$html .= '<img src="' . $atts['image'] . '" />';
		$html .= '<div class="blockquote">';
		$html .= do_shortcode( $content );
		$html .= '<footer>' . $atts['title'] . '</footer>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;

	}

	/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function attr() {

				$attr = fusion_builder_visibility_atts(
					$this->args['hide_on_mobile'],
					[
						'class' => 'fusion-recent-posts fusion-recent-posts-' . $this->recent_posts_counter . ' avada-container layout-' . $this->args['layout'] . ' layout-columns-' . $this->args['columns'],
					]
				);

				if ( $this->args['content_alignment'] && 'default' === $this->args['layout'] ) {
					$attr['class'] .= ' fusion-recent-posts-' . $this->args['content_alignment'];
				}

				if ( 'infinite' === $this->args['scrolling'] || 'load_more_button' === $this->args['scrolling'] ) {
					$attr['class']     .= ' fusion-recent-posts-infinite';
					$attr['data-pages'] = $this->args['max_num_pages'];
				}

				if ( 'load_more_button' === $this->args['scrolling'] ) {
					$attr['class'] .= ' fusion-recent-posts-load-more';
				}

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				return $attr;
			}

			/**
			 * Builds the section attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function section_attr() {
				return [
					'class' => 'fusion-columns columns fusion-columns-' . $this->args['columns'] . ' columns-' . $this->args['columns'],
				];
			}

			/**
			 * Builds the column attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function column_attr() {

				$columns = 3;
				if ( $this->args['columns'] ) {
					$columns = 12 / $this->args['columns'];
				}

				$attr = [
					'class' => 'post fusion-column column col col-lg-' . $columns . ' col-md-' . $columns . ' col-sm-' . $columns . '',
					'style' => '',
				];

				if ( '5' === $this->args['columns'] || 5 === $this->args['columns'] ) {
					$attr['class'] = 'post fusion-column column col-lg-2 col-md-2 col-sm-2';
				}

				if ( $this->args['animation_type'] ) {
					$animations = FusionBuilder::animations(
						[
							'type'      => $this->args['animation_type'],
							'direction' => $this->args['animation_direction'],
							'speed'     => $this->args['animation_speed'],
							'offset'    => $this->args['animation_offset'],
						]
					);

					$attr = array_merge( $attr, $animations );

					$attr['class'] .= ' ' . $attr['animation_class'];
					unset( $attr['animation_class'] );
				}

				return $attr;
			}

			/**
			 * Builds the slideshow attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @return array
			 */
			public function slideshow_attr() {

				$attr = [
					'class' => 'fusion-flexslider fusion-flexslider-loading flexslider',
				];

				if ( 'thumbnails-on-side' === $this->args['layout'] ) {
					$attr['class'] .= ' floated-slideshow';
				}

				if ( $this->args['hover_type'] ) {
					$attr['class'] .= ' flexslider-hover-type-' . $this->args['hover_type'];
				}

				return $attr;
			}

			/**
			 * Builds the image attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @param array $args The arguments array.
			 * @return array
			 */
			public function img_attr( $args ) {

				$attr = [
					'src' => $args['src'],
				];

				if ( $args['alt'] ) {
					$attr['alt'] = $args['alt'];
				}

				return $attr;
			}

			/**
			 * Builds the link attributes array.
			 *
			 * @access public
			 * @since 1.0
			 * @param array $args The arguments array.
			 * @return array
			 */
			public function link_attr( $args ) {

				$attr = [
					'aria-label' => the_title_attribute( [ 'echo' => false ] ),
				];

				if ( $this->args['hover_type'] ) {
					$attr['class'] = 'hover-type-' . $this->args['hover_type'];
				}

				return $attr;
			}

			/**
			 * Builds the content wrapper attributes array.
			 *
			 * @access public
			 * @since 1.5.2
			 * @return array
			 */
			public function content_attr() {
				return [
					'class' => 'recent-posts-content',
				];
			}

			/**
			 * Sets the necessary scripts.
			 *
			 * @access public
			 * @since 1.5.2
			 * @return void
			 */
			public function add_scripts() {

				global $fusion_settings;

				Fusion_Dynamic_JS::enqueue_script(
					'fusion-recent-posts',
					FusionBuilder::$js_folder_url . '/general/fusion-recent-posts.js',
					FusionBuilder::$js_folder_path . '/general/fusion-recent-posts.js',
					[ 'jquery' ],
					'1',
					true
				);

				Fusion_Dynamic_JS::localize_script(
					'fusion-recent-posts',
					'fusionRecentPostsVars',
					[
						'infinite_loading_text' => '<em>' . __( 'Loading the next set of posts...', 'fusion-builder' ) . '</em>',
						'infinite_finished_msg' => '<em>' . __( 'All items displayed.', 'fusion-builder' ) . '</em>',
					]
				);
			}

	/**
	 * Processes that must run when the plugin is activated.
	 *
	 * @static
	 * @access public
	 * @since 1.0
	 */
	public static function activation() {
		
	}
}