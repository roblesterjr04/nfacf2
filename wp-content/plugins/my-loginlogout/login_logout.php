<?php
/*
Plugin Name: My login/Logout 
Plugin URI:http://172.10.1.3:8056/Nagarjuna/firstplugin
Description:With this plugin you can now add a real log in/logout item menu with auto switch when user is logged in or not.it works both Custom menubar as well as Defult menubar.We have a flexibility to set custom redirect pages for login as well as logout.it works both custom menu bar as well as default menu bar 
Author: Nagarjun Sonti
Author URI: http://172.10.1.3:8056/Nagarjuna/firstplugin
Version: 2.4
License: GPL2 or later

*/
/*

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/

/* wp_mylogin table creation  and insert the initial data**/
if(!defined('MyLLTABLE')){
    global $wpdb;        
    $table_name = $wpdb->prefix . "mylogin";
  define('MyLLTABLE', $table_name);
}

if(!defined('MyLOCATIONTABLE')){
    global $wpdb;        
    $mytable_loc = $wpdb->prefix . "mylocation"; 
  define('MyLOCATIONTABLE', $mytable_loc);
}

register_activation_hook(__FILE__, 'mylogin_logout');

function mylogin_logout()
{
    global $wpdb;        
    $table_name = $wpdb->prefix . "mylogin";
    if($wpdb->get_var("show tables like '$table_name'") != $table_name) 
    {
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        login_page varchar(128) NOT NULL,
        logout_page varchar(128) NOT NULL,
        clogin_url VARCHAR(500) NULL,       
        created_date date NOT NULL,
        modified_date date  NULL,
        PRIMARY KEY (id)
        ) COLLATE utf8_general_ci;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $wpdb->insert( 
            $table_name, 
            array(  'login_page' =>'index.php', 
                    'logout_page' =>'index.php', 
                    'created_date' =>current_time( 'mysql' ), 
                    'modified_date' => '' 
                    ) );
    }
    create_nav_menu_location_table();
}

/* mylocation table creation  and insert the initial data**/
function create_nav_menu_location_table(){
    global $wpdb;
    global $locations;      
    $mytable_loc = $wpdb->prefix . "mylocation"; 

    if($wpdb->get_var("show tables like '$mytable_loc'") != $mytable_loc) 
    {
        $sql1="CREATE TABLE IF NOT EXISTS $mytable_loc (
                id int(5) NOT NULL AUTO_INCREMENT,
                menu_locations varchar(128) NOT NULL,
                value int(5) NOT NULL DEFAULT '1',
                created_date date NOT NULL,
                modified_date date  NULL,
                PRIMARY KEY (id)
	           ) COLLATE utf8_general_ci;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);

        $locations = get_registered_nav_menus();
        foreach( $locations as $key=>$location)
        {
            $wpdb->insert($mytable_loc,
            array('menu_locations'=>$key,'value'=>'1',
                    'created_date' =>current_time('mysql'),
                    'modified_date' => ''));	            
        }
    }
}
/* wp_mylogin table creation  and insert the initial data  end *************/ 

 /* wp_mylogin table creation  and insert the initial data  end *************/ 
register_deactivation_hook( __FILE__, 'drop_mylocation_table_deactivate' );
function drop_mylocation_table_deactivate()
{
  global $wpdb;  
  $sql = "DROP TABLE IF EXISTS ".MyLOCATIONTABLE.";";
  $wpdb->query($sql);

  $sql1 = "DROP TABLE IF EXISTS ".MyLLTABLE.";";
  $wpdb->query($sql1);    
}

/* Including css**/

function my_adding_styles() 
{
	wp_register_style('mystyle', plugins_url('/css/mystyle.css', __FILE__),false, '1.0.0', 'all');
	wp_enqueue_style('mystyle');
}
	 
add_action( 'admin_enqueue_scripts', 'my_adding_styles' );

function my_adding_scripts() 
{
	wp_register_script('jquery', 'http://code.jquery.com/jquery-latest.min.js', false);
    wp_enqueue_script('jquery');
	wp_register_script('myjs', plugins_url('/js/myjs.js', __FILE__), array('jquery'),'1.1', true);
	wp_enqueue_script('myjs');
}
	 
add_action( 'admin_enqueue_scripts', 'my_adding_scripts' );

/* including css end *******/

/* retreiving data from the table and set as global variable**/			
function wtnerd_global_vars() 
{
    global $wpdb;
    global $myloginid;
    global $mylogoutid;
    global $cu_lginurl;
    global $cu_logouturl;
    global $display_menu_locations;
    
    $myll_links = $wpdb->get_row( "SELECT * FROM ".MyLLTABLE."" );
    $myloginid= get_permalink($myll_links->login_page);
    $mylogoutid= get_permalink($myll_links->logout_page);
    
    if($myll_links->login_page=="index.php"){

        $myloginid= get_permalink();
        // $mylogoutid= get_permalink($myll_links->logout_page);

    }
    if($myll_links->logout_page=="index.php"){

        // $myloginid= get_permalink($myll_links->login_page);
        $mylogoutid= get_permalink();
    }
   
    $cu_lginurl= $myll_links->clogin_url;     

    $display_menu_locations=$wpdb->get_col("SELECT menu_locations FROM ".MyLOCATIONTABLE." WHERE value=1");
}
add_action( 'parse_query', 'wtnerd_global_vars' );

/* retreiving data from the table and set as global variable End ********/

/* Defult Menu Bar**/
function add_login_logout($items, $args)
{
    global $display_menu_locations;
    
    $items.=add_my_loginlogout_links();

    // foreach($display_menu_locations as $display_menu_location){

    //     if($args['theme_location']==$display_menu_location){
    //       $items.=add_my_loginlogout_links();  
    //     }
    // }   
    return $items;
}
add_filter('wp_list_pages', 'add_login_logout', 10, 2);

/* Defult Menu Bar  End  *************/


/* Custom Menu Bar  **/
function add_login_logout_link($items, $args)
{      
    global $display_menu_locations;    
    foreach($display_menu_locations as $display_menu_location){

        if($args->theme_location==$display_menu_location){
          $items.=add_my_loginlogout_links();  
        }
    }
    return $items;
}
add_filter('wp_nav_menu_items', 'add_login_logout_link', 10, 2);

/* Custom Menu Bar End **************/

function add_my_loginlogout_links()
{     
    if(is_user_logged_in())
    {
        $newitems=add_logout_link();                
        return $newitems;

    }else
    {   
        $newitems=add_login_link();                
        return $newitems;               
    }
}


function add_logout_link(){   
    global $mylogoutid;
    $newitems = '<li><a class="mylllmenu" title="Logout" href="'.wp_logout_url($mylogoutid).'">logout</a></li>';
    return $newitems;

}

function add_login_link(){
    global $myloginid;   
    global $cu_lginurl;

    if(!empty($cu_lginurl)){
          $newitems = '<li><a class="mylllmenu" title="Logout" href="'.$cu_lginurl.'">login</a></li>';
          return $newitems;  
        }else{
           $newitems = '<li><a class="mylllmenu" title="Login" href="'.wp_login_url($myloginid) .'">login</a></li>'; 
           return $newitems;
        }
} 

/* Creating Lable on admin side bar  **/
function plugin_admin_add_page()
{
	add_options_page( 'custom menu title', 'My Login/logout', 'manage_options', 'my-loginlogout/adminpage.php');
}
add_action('admin_menu', 'plugin_admin_add_page');
 /* Creating Lable on admin side bar  End ******/
function my2_enqueue($hook)
{
    if( 'my-loginlogout/adminpage.php' != $hook )
    return;
}
 
add_action( 'admin_enqueue_scripts', 'my2_enqueue' );
/* check the menu locations by using array functions and
 calling the respective functions for update the table**/
function update_all_menu_locatons($locations){    
    global $wpdb;   
    $mytable_loc = $wpdb->prefix . "mylocation";

    $nav_locations = $wpdb->get_col( "SELECT menu_locations FROM ".MyLOCATIONTABLE."" );

    if(!empty($locations))
    {
        $display_menu_locationarray=  array_intersect($nav_locations, $locations);

        if($display_menu_locationarray){
            display_updatequery($display_menu_locationarray);
        }
        
        $hide_menu_locationarray=  array_diff($nav_locations, $locations);
        if($hide_menu_locationarray){
            hide_updatequery($hide_menu_locationarray);
        }
    }else{

        hide_updatequery($nav_locations);
    }
//  return true;
}
/* set the location value to 1 for display**/
function display_updatequery($dis_locations){    
    global $wpdb;
    
    foreach($dis_locations as $dis_location){        
      $wpdb->update(MyLOCATIONTABLE,
                    array('value'=>1),
                    array('menu_locations'=>$dis_location),
                    array('%d'));
        
    }    
}
/* set the location value to 0 for hidding**/
function hide_updatequery($hid_locations){
    global $wpdb;
       
    foreach($hid_locations as $hid_location){        
        $wpdb->update(MyLOCATIONTABLE,
            array('value'=>0),
            array('menu_locations'=>$hid_location),
            array('%d'));        
    }    
    
}
/* fetch the data and return to call function**/
function get_mylogin_logoutLinks(){
    global $wpdb; 
    $redirect_links = $wpdb->get_row( "SELECT * FROM ".MyLLTABLE."" );
    
    return $redirect_links;
}
/* Login: validate the url field if it fail it assign to empty value**/
function check_loginurl($url){
        $result="";
        if(!filter_var($url, FILTER_VALIDATE_URL))
        {
            echo "<span style='color:red;font-weight:bold'>Login URL is not valid</span>";
        }else{
            $result=$url;             
            return $result;
        }
         return $result;
}
/* update the entire table **/
function update_mylinks($data){  

    $mylogin1=$data['page-dropdown1'];
    $mylogout1= $data['page-dropdown2'];
	
	$myclogin_url="";
	if(!empty($data['custom_login_url'])){
	$myclogin_url=esc_sql($data['custom_login_url']); 
	}

    
    global $wpdb;
    $wpdb->update( 
       MyLLTABLE, 
        array( 
        'login_page' => $mylogin1,  
        'logout_page' => $mylogout1,
        'clogin_url' => $myclogin_url,       
        'modified_date' => current_time( 'mysql' )), 
        array( 'id' => 1 ), 
        array( '%s','%s','%s'), array( '%d' ));
}

