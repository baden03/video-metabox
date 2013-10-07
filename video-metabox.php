<?php
/**
 * Plugin Name: video-metabox
 * Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
 * Description: Adds a video metabox plugin to your site.
 * Version: 0.1
 * Author: Jesse
 * Author URI: http://URI_Of_The_Plugin_Author
 * License: GPL2
 */

/*  Copyright 2013  Jesse Overright  (email : PLUGIN AUTHOR EMAIL)

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

add_action("admin_init", "add_video_metabox");
add_action("save_post", "save_video_metabox");


function add_video_metabox () {
         add_meta_box("video-meta", "Video", "video_meta_box", "post", "normal", "high");
    }

function video_meta_box () {
         global $post;

        // Verify
        echo'<input type="hidden" name="video_noncename" id="video_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';
        
        $video_id = get_post_meta($post->ID, 'video_id', true);
        $video_type = get_post_meta($post->ID, 'video_type', true);
        $video_thumb_url = get_post_meta($post->ID, 'video_thumb_url', true);
        
        if ($video_thumb_url != "")
        {
            echo '<img src="'.$video_thumb_url.'" style="margin: 5px 0 10px;" /><br />';    
        }
        ?>
        <label for="video_type" style="margin-right: 5px;">Video Type:</label>
        <select name="video_type">
            <option value="youtube" <?php if ($video_type == "youtube") echo ' selected';?>>YouTube</option>
            <option value="vimeo" <?php if ($video_type == "vimeo") echo ' selected';?>>Vimeo</option>
        </select>
        <label for="video_id" style="margin-right: 18px;margin-left:8px;">Video ID:</label>
        <input type="text" name="video_id" value="<?php echo $video_id; ?>" size="20" /><br />
        <p>Example Video IDs: www.youtube.com/watch?v=<b>hfbwoCqLgJo</b>, http://vimeo.com/<b>29491315</b></p>

        <?php
    }
    
function save_video_metabox( $post_id ) {
    global $post;
    
    // Verify
    if (isset($_POST['video_noncename'])) {
        if ( !wp_verify_nonce( $_POST["video_noncename"], plugin_basename(__FILE__) ))
            return $post_id;
    }
    
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
            
    // depending on the video type, get image location
    ini_set("allow_url_fopen",true);
    // VIMEO
    if ($video_type == "vimeo") {
        if ($video_thumb_url == "" && $video_id != "") {
            $video_thumb_url = get_vimeo_thumb($video_id);
        }
    }
    // YOUTUBE
    elseif ($video_type == "youtube") {
        if ($video_thumb_url == "" && $video_id != "") {
            $video_thumb_url = get_youtube_thumb($video_id);
        }
    }
    // if no video, remove thumbnail
    if ($video_id == "")
    {
        $video_thumb_url = "";
    }
    
    if(get_post_meta($post_id, 'video_thumb_url') == "")
    add_post_meta($post_id, 'video_thumb_url', $video_thumb_url, true);
    elseif($video_thumb_url != get_post_meta($post_id, 'video_thumb_url', true))
    update_post_meta($post_id, 'video_thumb_url', $video_thumb_url);
    elseif($video_thumb_url == "")
    delete_post_meta($post_id, 'video_thumb_url', get_post_meta($post_id, 'video_thumb_url', true));
    
}
// additional functions for video metabox 
function get_vimeo_thumb($vimeo)
{
    if ($vimeo == "")
        return "";
    $id = $vimeo;
    //forming API url
    $url = "http://vimeo.com/api/v2/video/".$id.".json";
    //curl request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $curlData = curl_exec($curl);
    curl_close($curl);
    
    //decoding json structure into array
    $arr = current(json_decode($curlData, true));
    
    /*  Vimeo Thumbnail Sizes:
     thumbnail_small => 100x75
     thumbnail_medium => 200x150
     thumbnail_large => 640x360
    */
    return $arr["thumbnail_large"];

}

function get_youtube_thumb($video_id)
{
    return "http://i.ytimg.com/vi/".$video_id."/mqdefault.jpg";
    
}

function render_video($video_id, $video_type, $video_width = 900, $return = false) {
    $video_height = round($video_width / 16 * 9);
    
    switch ($video_type) {
        case "vimeo":
            $embed = '<iframe src="http://player.vimeo.com/video/'.$video_id.'?title=0&byline=0&portrait=0&color=ffffff" width="'.$video_width.'" height="'.$video_height.'" frameborder="0" webkitAllowFullScreen allowFullScreen></iframe>';
            break;
        case "youtube":
            $embed = '<iframe width="'.$video_width.'" height="'.$video_height.'" src="http://www.youtube.com/embed/'.$video_id.'/?modestbranding=1&rel=0&showinfo=0" frameborder="0" allowfullscreen></iframe>';
            break;
        default:
            $embed = "";
            break;  
    }
    if ($video_id == "") return; // validate video, if no video id has been sent, don't render video
    if ($return)
        return $embed;
    else
        echo $embed;
}

// hook into the_content and display the video when applicable.
add_filter( 'the_content' , 'video_metabox_content_filter');

function video_metabox_content_filter( $content ) {
    global $post;
    if (get_post_meta($post->ID,'video_id',true) != "") {
        $content = render_video(get_post_meta($post->ID,'video_id',true),get_post_meta($post->ID,'video_type',true),640) . $content;
    }

    return $content;
}