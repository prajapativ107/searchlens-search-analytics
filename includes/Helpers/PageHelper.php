<?php
/**
 * Page helper.
 *
 * @package SearchLens
 */

namespace SearchLens\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Utility functions for dynamically retrieving page properties.
 */
final class PageHelper {

	/**
	 * Get the title of the current page dynamically using WordPress APIs.
	 *
	 * @return string The resolved page title.
	 */
	public static function get_current_page_title(): string {
		if ( is_front_page() && ! is_home() ) {
			$post_id = get_option( 'page_on_front' );
			if ( $post_id ) {
				$front_title = get_the_title( $post_id );
				if ( ! empty( $front_title ) ) {
					return $front_title;
				}
			}
		}

		if ( is_home() && ! is_front_page() ) {
			$post_id = get_option( 'page_for_posts' );
			if ( $post_id ) {
				$posts_title = get_the_title( $post_id );
				if ( ! empty( $posts_title ) ) {
					return $posts_title;
				}
			}
		}

		if ( is_front_page() || is_home() ) {
			return get_bloginfo( 'name' );
		}

		if ( is_singular() ) {
			return get_the_title();
		}

		if ( is_category() || is_tag() || is_tax() ) {
			return single_term_title( '', false );
		}

		if ( is_post_type_archive() ) {
			return post_type_archive_title( '', false );
		}

		if ( is_author() ) {
			$author = get_queried_object();
			if ( $author instanceof \WP_User ) {
				return $author->display_name;
			}
		}

		if ( is_search() ) {
			return sprintf(
			/* translators: %s: search query */
				__( 'Search Results for "%s"', 'searchlens-search-analytics' ),
				get_search_query()
			);
		}

		if ( is_archive() ) {
			add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
			$title = get_the_archive_title();
			remove_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
			return $title;
		}

		return get_bloginfo( 'name' );
	}

	/**
	 * Get the current page type dynamically using WordPress APIs.
	 *
	 * @return string The page type label.
	 */
	public static function get_current_page_type(): string {
		if ( is_front_page() || is_home() ) {
			return 'Home';
		}

		if ( is_search() ) {
			return 'Search Results';
		}

		if ( is_404() ) {
			return '404';
		}

		if ( is_singular() ) {
			$post_type = get_post_type();
			$obj       = get_post_type_object( $post_type );
			return $obj ? $obj->labels->singular_name : 'Post';
		}

		if ( is_category() ) {
			return 'Category';
		}

		if ( is_tag() ) {
			return 'Tag';
		}

		if ( is_tax() ) {
			$taxonomy = get_query_var( 'taxonomy' );
			$tax_obj  = get_taxonomy( $taxonomy );
			return $tax_obj ? $tax_obj->labels->singular_name : 'Taxonomy';
		}

		if ( is_post_type_archive() ) {
			$post_type = get_query_var( 'post_type' );
			if ( is_array( $post_type ) ) {
				$post_type = reset( $post_type );
			}
			$obj = get_post_type_object( $post_type );
			return $obj ? $obj->labels->name : 'Archive';
		}

		if ( is_author() ) {
			return 'Author';
		}

		if ( is_archive() ) {
			return 'Archive';
		}

		return 'Other';
	}

	/**
	 * Get the current page URL dynamically using WordPress APIs.
	 *
	 * @return string The dynamic URL.
	 */
	public static function get_current_page_url(): string {
		global $wp;
		$current_url = home_url( add_query_arg( array(), $wp->request ) );
		if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
			$current_url = add_query_arg( array(), $current_url );
		}
		return esc_url_raw( $current_url );
	}

	/**
	 * Resolve or clean the page title from a URL and/or a raw title.
	 *
	 * @param string $url       The page URL.
	 * @param string $raw_title The raw title received.
	 *
	 * @return string The cleaned/resolved page title.
	 */
	public static function resolve_page_title( string $url, string $raw_title = '' ): string {
		$url = esc_url_raw( $url );

		// 1. Try resolving using post ID from URL if it's a singular post.
		$post_id = url_to_postid( $url );
		if ( $post_id ) {
			$post_title = get_the_title( $post_id );
			if ( ! empty( $post_title ) ) {
				return $post_title;
			}
		}

		// 2. If it's the front page / home page URL:
		$home_url    = home_url( '/' );
		$cleaned_url = user_trailingslashit( $url );
		if ( $home_url === $cleaned_url || home_url() === $url || '/' === $url ) {
			$page_on_front = get_option( 'page_on_front' );
			if ( $page_on_front ) {
				$front_title = get_the_title( $page_on_front );
				if ( ! empty( $front_title ) ) {
					return $front_title;
				}
			}
			return get_bloginfo( 'name' );
		}

		// 3. Clean the raw title if provided.
		if ( ! empty( $raw_title ) ) {
			return self::clean_document_title( $raw_title );
		}

		// 4. Fallback.
		return get_bloginfo( 'name' );
	}

	/**
	 * Dynamically clean a browser document title by removing the site name,
	 * tagline, and common separators.
	 *
	 * @param string $raw_title The raw browser document title.
	 *
	 * @return string The cleaned page title.
	 */
	public static function clean_document_title( string $raw_title ): string {
		$site_name = get_bloginfo( 'name' );
		$tagline   = get_bloginfo( 'description' );

		// Decode entities just in case.
		$title = html_entity_decode( $raw_title, ENT_QUOTES, 'UTF-8' );

		// Remove site name and tagline if present.
		$targets = array();
		if ( ! empty( $site_name ) ) {
			$targets[] = $site_name;
		}
		if ( ! empty( $tagline ) ) {
			$targets[] = $tagline;
		}

		foreach ( $targets as $target ) {
			$title = str_ireplace( $target, '', $title );
		}

		// Clean up separators at start/end or extra spacing.
		$title = preg_replace( '/^[ \t\r\n\v\f|»»\-–—:·\•\s]+|[ \t\r\n\v\f|»»\-–—:·\•\s]+$/u', '', $title );
		$title = preg_replace( '/\s+/', ' ', $title );

		return trim( $title );
	}
}
