<?php

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/song-gigs/(?P<id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'get_gigs_for_song',
        'args' => array(
            'id' => array(
                'required' => true,
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                },
            ),
        ),
        'permission_callback' => '__return_true',
    ));
});
