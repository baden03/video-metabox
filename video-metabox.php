<?php
/**
 * Plugin Name: Video Metabox
 * Plugin URI: https://github.com/jesseoverright/video-metabox
 * Description: Adds a video metabox plugin to your site.
 * Version: 1.1
 * Author: Jesse Overright
 * Author URI: http://about.me/joverright
 * License: GPL2
 */

/*  Copyright 2013  Jesse Overright  (email : jesseoverright@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('admin_init', 'add_video_metabox');
add_action('save_post', 'save_video_metabox');

add_action('init', 'add_video_metabox_css');

function add_video_metabox () {
     add_meta_box( 'video-metabox', 'Video', 'video_metabox', 'post', 'normal', 'high');
     wp_enqueue_style( 'video-metabox-css', plugins_url( 'video-metabox.css', __FILE__) );
}

function add_video_metabox_css() {
    wp_enqueue_style( 'video-metabox-css', plugins_url( 'video-metabox.css', __FILE__) );
}    

function video_metabox () {
    global $post;

    // Verify data hasn't been tampered with
    echo'<input type="hidden" name="video_noncename" id="video_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
    
    $video_id = get_post_meta($post->ID, 'video_id', true);
    $video_type = get_post_meta($post->ID, 'video_type', true);

    if ($video_id != '' && $video_type != '') {
        render_video($video_id, $video_type);
    }
    ?>
    <label>Video Type:
    <select name="video_type">
        <option value="youtube" <?php if ($video_type == "youtube") echo ' selected';?>>YouTube</option>
        <option value="vimeo" <?php if ($video_type == "vimeo") echo ' selected';?>>Vimeo</option>
    </select></label>
    <label>Video ID:
    <input type="text" name="video_id" value="<?php echo $video_id; ?>" size="20" /></label>
    <p>Example Video IDs: www.youtube.com/watch?v=<b>hfbwoCqLgJo</b>, http://vimeo.com/<b>29491315</b></p>
    <?php
}
    
function save_video_metabox( $post_id ) {    
    // Verify data hasn't been tampered with
    if ( !wp_verify_nonce( $_POST["video_noncename"], plugin_basename(__FILE__) ))
        return $post_id;
    
    // New, Update, and Delete
    $data = $_POST['video_id'];
    $current_video = get_post_meta($post_id, 'video_id',true);
        
    if(get_post_meta($post_id, 'video_id') == "")
    add_post_meta($post_id, 'video_id', $data, true);
    elseif($data != get_post_meta($post_id, 'video_id', true))
    update_post_meta($post_id, 'video_id', $data);
    elseif($data == "")
    delete_post_meta($post_id, 'video_id', get_post_meta($post_id, 'video_id', true));
    
    $data = $_POST['video_type'];

    if(get_post_meta($post_id, 'video_type') == "")
    add_post_meta($post_id, 'video_type', $data, true);
    elseif($data != get_post_meta($post_id, 'video_type', true))
    update_post_meta($post_id, 'video_type', $data);
    elseif($data == "")
    delete_post_meta($post_id, 'video_type', get_post_meta($post_id, 'video_type', true));  
    
    // saving external links to video thumbnails
    $video_thumb_url = get_post_meta($post_id, 'video_thumb_url', true);
    $video_id = get_post_meta($post_id, 'video_id', true);
    $video_type = get_post_meta($post_id, 'video_type', true);
}

function scrape_url($video_url) {
    $urlquerystring = parse_url($video_url, PHP_URL_QUERY);
    parse_str($urlquerystring, $vars);

    #$vars['v'] = "youtube id";

}

function render_video($video_id, $video_type, $return_rendered_video = false) {
    
    switch ($video_type) {
        case 'vimeo':
            $embed = "<div class=\"video-metabox\"><iframe src=\"http://player.vimeo.com/video/{$video_id}?title=0&byline=0&portrait=0&color=ffffff\" frameborder=\"0\" webkitAllowFullScreen allowFullScreen></iframe></div>";
            break;
        case 'youtube':
            $embed = "<div class=\"video-metabox\"><iframe src=\"http://www.youtube.com/embed/{$video_id}/?modestbranding=1&rel=0&showinfo=0\" frameborder=\"0\" allowfullscreen></iframe></div>";
            break;
        default:
            $embed = '';
            break;  
    }
    if ($video_id == '') return; // validate video, if no video id has been sent, don't render video
    if ($return_rendered_video)
        return $embed;
    else
        echo $embed;
}

// hook into the_content and display the video when applicable.
add_filter( 'the_content' , 'video_metabox_content_filter');

function video_metabox_content_filter( $content ) {
    global $post;
    if (get_post_meta($post->ID,'video_id',true) != '') {
        $content = render_video(get_post_meta($post->ID,'video_id',true),get_post_meta($post->ID,'video_type',true),640) . $content;
    }

    return $content;
}