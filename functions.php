<?php namespace linkclick;
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
// include_once realpath('../../../wp-load.php');
include_once 'variables.php';

 function make_link_from_ticket($ticket){
     return plugins_url(basename(plugin_dir_path(__FILE__))."/view.php?ticket={$ticket}");
 }
function add_new_link($target){
    global $wpdb;
    global $db_links;
    $ticket = get_new_ticket($target);
    $wpdb->insert(
        $db_links,
        [
            'Ticket' => $ticket,
            'Target' => $target
        ]
    );
    return $ticket;
} 
function get_new_ticket($link = null){
    return str_replace('.','_',uniqid($link == null ? md5($link) : '',true)); 
}

function get_categories_tree(){
    global $wpdb;
    global $lc_db_category;
    global $categories;
    global $tree;
    $categories = $wpdb->get_results("SELECT * FROM {$lc_db_category}");
    $tree = [];
    function add_subcategories($masterid,$indentation_level){
        global $categories;
        global $tree;
        global $masterid2;
        $masterid2 = $masterid;
        $kids = array_filter($categories, function($cat){
            global $masterid2;
            return $cat->MasterCategoryID == $masterid2;
        });
        foreach ($kids as $key => $cat) {
            $cat->DisplayName = str_pad("",$indentation_level,"-")." ".$cat->Name;
            $tree[] = $cat;
            add_subcategories($cat->CategoryID,$indentation_level+1);
        }
    }
    // foreach ($categories as $key => $category) {
    //     $categories[$key]->DisplayName = $category->Name;
    // }
    add_subcategories(null,0);
    // print_r($categories);
    // print_r($tree);
    return $tree;
}

function get_new_file_name($filepathname){
    $finfo = pathinfo($filepathname);
    $noext = $finfo['dirname'].DIRECTORY_SEPARATOR.$finfo['filename'];
    $files = glob("{$noext}.*");
    if(sizeof($files) == 0){
        return $filepathname;
    }
    $files = glob("{$noext}-*");
    $last = end($files);
    if(preg_match('/-(\d)\.[^.\s]+/m',$last,$matches)){
        print_r($matches);
        $index = intval($matches[1]) + 1;
        return "{$noext}-{$index}.{$finfo['extension']}";
    }
    return "{$noext}-1.{$finfo['extension']}";
}

function secure_media($post_id){
    // global $wpdb;
    // $wpdb->get_row()
    $file = get_attached_file($post_id);
    print_r($file);
    return secure_file($file);
}
function secure_file($filepath){
    return true;
}
function unsecure_media($post_id){
    $file = get_attached_file($post_id);
    print_r($file);
    return unsecure_file($file);
}
function unsecure_file($filepath){
    return true;
}

function is_access_url($url){
    // echo $url;
    global $wpdb;
    $post = $wpdb->get_row("SELECT * FROM {$wpdb->posts}
    WHERE guid LIKE '%{$url}%'");
    if($post === NULL){
        return true;
    }
    return is_access($post->ID);
}
function is_access($post_id){
    global $wpdb;
    global $lc_db_link;
    $record = $wpdb->get_row("SELECT lcl.Secure FROM {$wpdb->posts} p
    LEFT JOIN {$lc_db_link} lcl ON p.ID = lcl.PostId
    WHERE p.ID = {$post_id}");
    // echo 1;
    // print_r($record);
    if($record === NULL || $record->Secure != true){
        // echo 2;
        return true;
    }else{
        // echo 3;
        return is_user_logged_in() == 1 ? true : false;
    }
}

function log_download_of_path($filepathname){
    // echo 'log_download_of_path';
    global $wpdb;
    global $lc_db_link;
    global $lc_db_log;
    $record = $wpdb->get_row("SELECT * FROM {$wpdb->posts} p
    LEFT JOIN {$lc_db_link} lcl ON p.ID = lcl.PostId
    WHERE p.guid LIKE '%{$filepathname}'");
    // print_r($record);
    $LinkId = $record->Id;
    log_download_of($LinkId);
}
function log_download_of($LinkId){
    // echo 'log_download_of';
    // print_r($LinkId);
    if($LinkId == NULL){
        return;
    }
    global $wpdb;
    global $lc_db_log;
    $current_user = wp_get_current_user();
    $cuid = $current_user->ID;
    $wpdb->insert(
        $lc_db_log,
        [
            'LinkId' => $LinkId,
            'UserId' => $cuid
        ]
    );
}