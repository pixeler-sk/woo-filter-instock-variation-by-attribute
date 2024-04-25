<?php
//hide in posts results
add_filter( 'posts_results', 'px_posts_results_filter' );
function px_posts_results_filter( $posts ) {

	$filtered_posts = [];

	$attributes = [];

	$attribute_taxonomies = wc_get_attribute_taxonomies();

	foreach ($attribute_taxonomies as $attribute_taxonomy) {
		$attributes[] = $attribute_taxonomy->attribute_name;
	}

	if ( ! is_admin() && ( is_shop() || is_product_category() || is_product_taxonomy() ) ) {

		if ( ! empty( $_GET['stock_status'] ) ) {

			$stock_status = explode( ',', $_GET['stock_status'] );

			//only if parameter stock_status contain instock
			if ( in_array( 'instock', $stock_status ) ) {

				$filter_array = px_get_filter_parameter_values( $attributes );

				//only if set same filter parameters by attributes
				if ( ! empty( $filter_array ) ) {

					foreach ( $posts as $post ) {

						$product_id = $post->ID;

						$variation_ids = px_get_variations_by_attributes( $product_id, $filter_array );

						if ( ! empty( $variation_ids ) ) {
							foreach ( $variation_ids as $variation_id ) {
								$product_variation = wc_get_product( $variation_id );

								if ( $product_variation->is_in_stock() ) {

									//$filtered_posts[$variation_id] = get_post($variation_id); //Add variation post

									$filtered_posts[ $product_id ] = $post; //Add parent product
								}
							}

						}


					}
				}

			}
		}
	}

	if ( ! empty( $filtered_posts ) ) {
		return array_values( $filtered_posts );
	} else {
		return $posts;
	}

}

//helpers
function px_get_filter_parameter_values( $attributes ): array {
	$filter_array = [];
	foreach ( $attributes as $attribute ) {
		$parameter_name = "filter_" . $attribute;

		if ( isset( $_GET[ $parameter_name ] ) ) {
			$attribute_values           = explode( ',', $_GET[ $parameter_name ] );
			$filter_array[ $attribute ] = $attribute_values;
		}
	}

	return $filter_array;
}

function px_get_variations_by_attributes( $product_id, $attributes ): array {
	$product = wc_get_product( $product_id );

	$variation_ids = [];

	if ( $product->get_type() == 'variable' ) {

		$variations = $product->get_available_variations();

		if ( $variations ) {

			foreach ( $variations as $variation ) {

				$is_match_variation = true;
				foreach ( $attributes as $attribute_key => $attribute_values ) {
					$variation_attribute_value = $variation['attributes'][ 'attribute_pa_' . $attribute_key ] ?? [];

					//if only one from attributes is not match stop
					if ( ! in_array( $variation_attribute_value, $attribute_values ) ) {
						$is_match_variation = false;
						break;
					}

				}

				if ( $is_match_variation ) {
					$variation_ids[] = $variation['variation_id'];
				}

			}
		}

	}

	return $variation_ids;
}
