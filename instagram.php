<?php
/*
Copyright © 2015 It Spiders

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/


add_action('widgets_init', function() {
    register_widget('uix_instagram_widget' );
});

class uix_instagram_widget extends WP_Widget {

    function __construct() {
        $widget_ops = array( 'classname' => 'uix-instagram-feed', 'description' => esc_html__( 'Displays your latest Instagram photos', 'uix' ) );
        parent::__construct( 'uix-instagram-feed', UIX::meta("name").' - '. esc_html__( 'Instagram', 'uix' ), $widget_ops );
    }

    function widget( $args, $instance ) {

        extract( $args, EXTR_SKIP );

        $title    = empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', $instance['title'] );
        $username = empty( $instance['username'] ) ? '' : $instance['username'];
        $size     = empty( $instance['size'] ) ? 'large' : $instance['size'];
        $layout   = empty( $instance['layout'] ) ? '' : $instance['layout'];
        $limit    = empty( $instance['number'] ) ? 9 : $instance['number'];

        echo ($before_widget);

        if ( !empty( $title ) ) { echo ($before_title . $title . $after_title); };

        do_action( 'uix_before_widget', $instance );

        if ( trim($username) != '' ) {

            $media_array = $this->scrape_instagram( $username, $limit );

            if ( is_wp_error( $media_array ) ) {

                echo ($media_array->get_error_message());

            } else {

                // filter for images only?
                if ( $images_only = apply_filters( 'uix_insta_images_only', FALSE ) ) {
                    $media_array = array_filter( $media_array, array( $this, 'images_only' ) );
                }

                // determine if template is available
                $template_available = locate_template('layouts/widgets/instagram'.($layout ? ('_'.$layout):'').'.php');

                if ($template_available) {
                    $items = $media_array;
                    include($template_available);
                } else {

                ?>

                    <ul class="uk-grid uk-grid-small uk-grid-width-medium-1-3" data-uk-grid-margin>
                        <?php foreach ( $media_array as $item ): ?>
                        <li>
                            <img src="<?php echo esc_url( $item[$size] )?>"  alt="<?php echo esc_attr( $item['description'] )?>" title="<?php echo esc_attr( $item['description'] )?>">
                            <a class="uk-position-cover" href="<?php echo esc_url( $item['link'] )?>" target="_blank"></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php
                }
            }
        }

        do_action( 'uix_after_widget', $instance );

        echo ($after_widget);
    }

    function form( $instance ) {

        $instance = wp_parse_args( (array) $instance, array(
            'title'    => esc_html__( 'Instagram', 'uix' ),
            'username' => '',
            'layout'   => '',
            'size'     => '',
            'link'     => esc_html__('Follow Us', 'uix'),
            'number'   => 9
        ));

        $title    = esc_attr( $instance['title'] );
        $username = esc_attr( $instance['username'] );
        $layout   = esc_attr( $instance['layout'] );
        $size     = esc_attr( $instance['size'] );
        $number   = absint( $instance['number'] );

        $layouts = array();

        foreach(array(STYLESHEETPATH,TEMPLATEPATH) as $tplpath) {

            if (file_exists($tplpath.'/layouts/widgets')) {

                if ($handle = opendir($tplpath.'/layouts/widgets')) {

                    while (false !== ($entry = readdir($handle))) {

                        if (preg_match('#^instagram_(.+)\.php#', $entry, $match)) {
                            $layouts[] = $match[1];
                        }
                    }

                    closedir($handle);
                }
            }
        }

        $layouts = array_unique($layouts);

        ?>
        <p><label for="<?php echo esc_attr($this->get_field_id( 'title' )); ?>"><?php esc_html_e('Title', 'uix' ); ?>: <input class="widefat" id="<?php echo esc_attr($this->get_field_id( 'title' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
        <p><label for="<?php echo esc_attr($this->get_field_id( 'username' )); ?>"><?php esc_html_e('Username', 'uix' ); ?>: <input class="widefat" id="<?php echo esc_attr($this->get_field_id( 'username' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'username' )); ?>" type="text" value="<?php echo esc_attr($username); ?>" /></label></p>
        <p><label for="<?php echo esc_attr($this->get_field_id( 'number' )); ?>"><?php esc_html_e('Number of photos', 'uix' ); ?>: <input class="widefat" id="<?php echo esc_attr($this->get_field_id( 'number' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'number' )); ?>" type="text" value="<?php echo esc_attr($number); ?>" /></label></p>
        <p><label for="<?php echo esc_attr( $this->get_field_id( 'size' ) ); ?>"><?php esc_html_e( 'Photo Size', 'uix' ); ?>:</label>
            <select id="<?php echo esc_attr( $this->get_field_id( 'size' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'size' ) ); ?>" class="widefat">
                <option value="thumbnail" <?php selected( 'thumbnail', $size ) ?>><?php esc_html_e( 'Thumbnail', 'uix' ); ?></option>
                <option value="small" <?php selected( 'small', $size ) ?>><?php esc_html_e( 'Small', 'uix' ); ?></option>
                <option value="large" <?php selected( 'large', $size ) ?>><?php esc_html_e( 'Large', 'uix' ); ?></option>
                <option value="original" <?php selected( 'original', $size ) ?>><?php esc_html_e( 'Original', 'uix' ); ?></option>
            </select>
        </p>
        <?php if(count($layouts)): ?>
            <p><label for="<?php echo esc_attr($this->get_field_id( 'layout' )); ?>"><?php esc_html_e( 'Layout', 'uix' ); ?>:</label>
                <select id="<?php echo esc_attr($this->get_field_id( 'layout' )); ?>" name="<?php echo esc_attr($this->get_field_name( 'layout' )); ?>" class="widefat">
                    <option value="">Grid</option>
                    <?php foreach($layouts as $l):?>
                    <option value="<?php echo esc_attr($l)?>" <?php selected( $l, $layout ) ?>><?php echo ucfirst($l); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
        <?php endif; ?>
        <?php
    }

    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title']    = strip_tags( $new_instance['title'] );
        $instance['username'] = trim( strip_tags( $new_instance['username'] ) );
        $instance['number']   = !absint( $new_instance['number'] ) ? 9 : $new_instance['number'];
        $instance['size']     = ( ( $new_instance['size'] == 'thumbnail' || $new_instance['size'] == 'large' || $new_instance['size'] == 'small' || $new_instance['size'] == 'original' ) ? $new_instance['size'] : 'large' );
        $instance['layout']   = strip_tags( $new_instance['layout'] );
        return $instance;
    }

    // based on https://gist.github.com/cosmocatalano/4544576
    function scrape_instagram( $username, $slice = 9 ) {

        $username = strtolower( $username );
        $username = str_replace( '@', '', $username );

        if ( false === ( $instagram = get_transient( 'instagram-media-new-'.sanitize_title_with_dashes( $username ) ) ) ) {

            $remote = wp_remote_get( 'http://instagram.com/'.trim( $username ) );

            if ( is_wp_error( $remote ) )
                return new WP_Error( 'site_down', esc_html__( 'Unable to communicate with Instagram.', 'uix' ) );

            if ( 200 != wp_remote_retrieve_response_code( $remote ) )
                return new WP_Error( 'invalid_response', esc_html__( 'Instagram did not return a 200.', 'uix' ) );

            $shards = explode( 'window._sharedData = ', $remote['body'] );
            $insta_json = explode( ';</script>', $shards[1] );
            $insta_array = json_decode( $insta_json[0], TRUE );

			// Peak inside the array
			//echo '<pre>';
//			print_r($insta_array);
//			echo '</pre>';

            if ( !$insta_array )
                return new WP_Error( 'bad_json', esc_html__( 'Instagram has returned invalid data.', 'uix' ) );

            if ( isset( $insta_array['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'] ) ) {
               $images = $insta_array['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];
            } else {
                return new WP_Error( 'bad_json_2', esc_html__( 'Instagram has returned invalid data.', 'uix' ) );
            }

            if ( !is_array( $images ) )
                return new WP_Error( 'bad_array', esc_html__( 'Instagram has returned invalid data.', 'uix' ) );

            $instagram = array();

            foreach ( $images as $image ) {

				$image = $image['node'];
				
                $image['thumbnail_src'] = preg_replace( '/^https?\:/i', '', $image['thumbnail_src'] );
                $image['display_url'] = preg_replace( "/^http:/i", "",  $image['display_url'] );

                // handle both types of CDN url
                if ( ( strpos( $image['thumbnail_src'], 's640x640' ) !== false ) ) {
                    $image['thumbnail'] = str_replace( 's640x640', 's160x160', $image['thumbnail_src'] );
                    $image['small'] = str_replace( 's640x640', 's320x320', $image['thumbnail_src'] );
                } else {
                    $urlparts = wp_parse_url( $image['thumbnail_src'] );
                    $pathparts = explode( '/', $urlparts['path'] );
                    array_splice( $pathparts, 3, 0, array( 's160x160' ) );
                    $image['thumbnail'] = '//' . $urlparts['host'] . implode( '/', $pathparts );
                    $pathparts[3] = 's320x320';
                    $image['small'] = '//' . $urlparts['host'] . implode( '/', $pathparts );
                }

                $image['large'] = $image['thumbnail_src'];

                if ( $image['is_video']  == true ) {
                    $type = 'video';
                } else {
                    $type = 'image';
                }

                $caption = esc_html__( 'Instagram Image', 'uix' );
                if ( ! empty( $image['caption'] ) ) {
                    $caption = $image['caption'];
                }

                $instagram[] = array(
                    'description' => $caption,
                    'link'        => trailingslashit( '//instagram.com/p/' . $image['shortcode'] ),
                    'time'        => $image['date'],
                    'comments'    => $image['comments']['count'],
                    'likes'       => $image['likes']['count'],
                    'thumbnail'   => $image['large'],
                    'small'       => $image['large'],
                    'large'       => $image['large'],
                    'original'    => $image['display_src'],
                    'type'        => $type
                );
            }

            // do not set an empty transient - should help catch private or empty accounts
            if ( ! empty( $instagram ) ) {
               $instagram = json_encode( $instagram );
                set_transient( 'instagram-media-new-'.sanitize_title_with_dashes( $username ), $instagram, apply_filters( 'uix_instagram_cache_time', HOUR_IN_SECONDS*2 ) );
            }
        }
		
		

        if ( ! empty( $instagram ) ) {

            $instagram = json_decode( $instagram, true );
            return array_slice( $instagram, 0, $slice );

        } else {

            return new WP_Error( 'no_images', esc_html__( 'Instagram did not return any images.', 'uix' ) );

        }
    }

    function images_only( $media_item ) {

        if ( $media_item['type'] == 'image' )
            return true;

        return false;
    }
}
