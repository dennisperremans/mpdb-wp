<?php

function get_gigs_for_song( $request ) {
    $song_id = $request->get_param( 'id' );

    if ( ! $song_id ) {
        return new WP_Error( 'missing_id', 'Song ID is required', array( 'status' => 400 ) );
    }

    $args = array(
        'post_type'      => 'gig',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'songs',
                'value'   => '"' . $song_id . '"',
                'compare' => 'LIKE',
            ),
        ),
    );

    $query = new WP_Query( $args );
    $gigs  = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            // Get songs related to this gig (array of post IDs)
            $songs_field = get_field( 'songs' );
            $songs_list  = [];

            if ( $songs_field && is_array( $songs_field ) ) {
                foreach ( $songs_field as $song_post ) {
                    // song_post can be post object or ID, normalize to ID
                    $song_post_id = is_object( $song_post ) ? $song_post->ID : $song_post;
                    $songs_list[] = get_the_title( $song_post_id );
                }
            }

            $gigs[] = array(
                'id'         => get_the_ID(),
                'venue_name' => get_field( 'venue_name' ),
                'city'       => get_field( 'city' ),
                'country'    => get_field( 'country' ),
                'date'       => get_the_date( 'Y-m-d' ),
                'songs'      => $songs_list,
            );
        }
        wp_reset_postdata();
    }

    return rest_ensure_response( $gigs );
}
