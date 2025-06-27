<?php
/**
 * Custom “filtered gigs” REST handler
 *
 *  Endpoint:  /wp-json/custom/v1/gigs
 *  Query params:
 *      - venue_name  (string, optional, LIKE match)
 *      - country     (string, optional, LIKE match)
 *      - city        (string, optional, LIKE match)
 *      - keyword     (string, optional, matches title/content/acf/songs)
 *      - page        (int,   optional, defaults 1)
 *      - per_page    (int,   optional, defaults 10)
 */

function custom_api_handle_filtered_gigs( WP_REST_Request $request ) {

    /* ---------------- Build meta_query from filters ---------------- */
    $meta_query = [ 'relation' => 'AND' ];

    $maybe_add = function ( $key, $value ) use ( &$meta_query ) {
        if ( ! empty( $value ) ) {
            $meta_query[] = [
                'key'     => $key,
                'value'   => $value,
                'compare' => 'LIKE',
            ];
        }
    };

    $maybe_add( 'venue_name', $request->get_param( 'venue_name' ) );
    $maybe_add( 'country',    $request->get_param( 'country'    ) );
    $maybe_add( 'city',       $request->get_param( 'city'       ) );

    /* ---------------- Advanced keyword search ---------------- */
    $keyword = $request->get_param( 'keyword' );
    $keyword_subqueries = [];

    if ( ! empty( $keyword ) ) {
        // Search for songs that match the keyword
        $song_ids = get_posts( [
            'post_type'      => 'song',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            's'              => $keyword,
        ] );

        $keyword_subqueries = [
            'relation' => 'OR',
            [
                'key'     => 'venue_name',
                'value'   => $keyword,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'city',
                'value'   => $keyword,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'country',
                'value'   => $keyword,
                'compare' => 'LIKE',
            ],
        ];

        if ( ! empty( $song_ids ) ) {
            foreach ( $song_ids as $song_id ) {
                $keyword_subqueries[] = [
                    'key'     => 'songs',
                    'value'   => '"' . $song_id . '"',
                    'compare' => 'LIKE',
                ];
            }
        }

        $meta_query[] = $keyword_subqueries;
    }

    /* ---------------- WP_Query ---------------- */
    $page     = max( (int) $request->get_param( 'page' ), 1 );
    $per_page = max( (int) $request->get_param( 'per_page' ), 10 );

    $query_args = [
        'post_type'      => 'gig',
        'post_status'    => 'publish',
        'paged'          => $page,
        'posts_per_page' => $per_page,
        'meta_query'     => $meta_query,
    ];

    // Only include 's' if keyword is present
    if ( ! empty( $keyword ) ) {
        $query_args['s'] = $keyword;
    }

    $query = new WP_Query( $query_args );

    /* ---------------- Convert posts to same shape as /wp/v2/gig ---------------- */
    $controller = new WP_REST_Posts_Controller( 'gig' );
    $items      = [];

    foreach ( $query->posts as $post ) {
        $data     = $controller->prepare_item_for_response( $post, $request );
        $items[]  = $controller->prepare_response_for_collection( $data );
    }

    /* ---------------- Build response ---------------- */
    $response = new WP_REST_Response( $items );
    $response->header( 'X-WP-Total',      (int) $query->found_posts );
    $response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

    return $response;
}
