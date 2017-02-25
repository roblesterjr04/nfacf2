<h2>My Login/Logout Admin Page</h2>
<div align="left" id="my_div" class="my_div">
<!--Submitting the form-->
<?php
    if ( $_REQUEST['page'] == 'mylogin_send' && isset( $_POST['submit'] ) )
    {  
	$myclogin_url="";

	if(!empty($_POST['custom_login_url']))
    {
	$myclogin_url=esc_sql($_POST['custom_login_url']); 
	}
        
        if(!empty($myclogin_url)){
        	$valuel=check_loginurl($myclogin_url);
        	$_POST['custom_login_url']=$valuel;        	       	        	
        }else{
        	$_POST['custom_login_url']="";        	      	
        }
        
        update_mylinks($_POST);
      
        $menu_array= update_all_menu_locatons($_POST['location']);      
  	} 
?>
<!--Submitting the form End-->
<form method="post" action="" id="send-form" enctype="multipart/form-data">	    
    <input type="hidden" name="page" value="mylogin_send" />
    <table  width="100%" cellpadding="0" cellspacing="0" border="0" class="display" id="example">
    <tr>
        <td colspan="2">
        <h3 class="my-head">Select redirection Pages after Login/logout</h3>
        
        </td>
    </tr>
    <tr>        
        <td>
        <?php $my_login_link=get_mylogin_logoutLinks(); ?>

        <div class="divcontent">
	        <div class="divlink" id="login-dropdown">
	        <div class="divlink">Login  </div>
		        <select class="myselect" name="page-dropdown1" id="page-dropdown1" >  
		            <option value="index.php"><?php echo esc_attr(__('Select page')); ?></option> 
		            <option value="<?php echo get_option('page_on_front'); ?>"><?php echo esc_attr(__('Front page')); ?></option> 
		                <?php 
		                $pages = get_pages(); 
		                foreach ($pages as $pagg) 
		                {
		                	if($pagg->ID==$my_login_link->login_page){
		                		$option = '<option value="'.$pagg->ID.'" selected="selected">';

		                	}else{
		                		$option = '<option value="'.$pagg->ID.'">';
		                	}
		                    
		                    $option .= $pagg->post_title;
		                    $option .= '</option>';
		                    echo $option;      
		                                
		                }
		               ?>
		            </select>
            </div>
            <div class="divlink"><button type="button"  name="show_custom_login_button" id="show_custom_login_button"> 
             Other Click!!</button>
         	</div>
          	<div style="display:none;" id="custom_loginid">
            
            <div class="divlink">Custom Url for Login</div>
            <div class="divlink">
            <input type="text" name="custom_login_url" id="custom_login_url" >
            <span style="color:#999">Example:http://www.abc/register.php</span></div>
            <button type="button"  name="hide_custom_login_button" id="hide_custom_login_button"> 
             remove!!</button>
            </div>  
        </div>           
        </td>
      
    </tr>
    <tr>
        <td >&nbsp</td>
    </tr>  
    <tr> 
       <td>
        <div class="divcontent">
	        <div class="divlink" id="logout-dropdown">
	        	<div class="divlink">Logout  </div>
	            <select  class="myselect" name="page-dropdown2" id="page-dropdown2"> 
             		<option value="index.php"><?php echo esc_attr(__('Select page')); ?></option> 
	             	<option value="<?php echo get_option('page_on_front'); ?>"><?php echo esc_attr(__('Front page')); ?></option> 
	              <?php 
	                      $pages = get_pages(); 
	                      foreach ($pages as $pagg) {
	                      if($pagg->ID==$my_login_link->logout_page){
	                      	$option = '<option value="'.$pagg->ID.'" selected="selected">';
	                      	}else{

	                      		$option = '<option value="'.$pagg->ID.'">';
	                      	}
	                      
	                      $option .= $pagg->post_title;
	                      $option .= '</option>';
	                      echo $option; }
	                      ?>
	            </select>
            </div>
       	</div> 
        </td>        
    </tr>
    <tr>
        <td >&nbsp</td>
    </tr>
    <tr>
        <td >
        <h3 class="my-head">Where You Want <span style="float:right;color:green"><span >Note:</span> 
  This section works only for custum menubar</span>  </h3>
        <p> Select the Check Box ,where the login/logout link will be active </p>

        </td>
    </tr>
<?php
$locations = get_registered_nav_menus();

foreach( $locations as $key=>$location)
{   
   	global $wpdb;   
 	$menu_checked=$wpdb->get_row("SELECT value FROM $mytable_loc WHERE menu_locations='$key'");
?>
    <tr>
        <td >
        	<div class="divlink"><?php echo $location;?> </div>
        	<div class="divlink">
           	 <input  type="checkbox" name="location[]" value="<?php echo $key;?>" <?php if($menu_checked->value==1){ echo "checked";}?>>
        	</div>
        </td>
    </tr>
    <tr><td >&nbsp</td>
    </tr>    
<?php }?>
    <tr>
        <td  align="right"> 
            <p class="submit"><input type="submit" value="Save" class="button-primary" id="submit" name="submit"></p>
        </td>
    </tr>
    </table>
</form>

<div>
    <?php
    $redirect_links=get_mylogin_logoutLinks();
    $myloginid= $redirect_links->login_page;
    $mylogoutid= $redirect_links->logout_page;
    $cloginUrl= $redirect_links->clogin_url;    
     ?>
    <p>Current Login Redirect page is &nbsp&nbsp=&nbsp

    <b> <?php if(!empty($cloginUrl)){
    	echo $cloginUrl;

    }else if(get_the_title($myloginid)=="")
    			{echo "front-page";
    			}else
    			{ echo get_the_title($myloginid);
    			} ?>
	</b></p>
    <p>Current Logout Redirect page is &nbsp&nbsp=&nbsp    
    <b>
   <?php  if(get_the_title($mylogoutid)==""){echo "front-page";}else{ echo get_the_title($mylogoutid);} ?></b></p>
    <a style="float:right;color:red;padding-right: 17px;" href="https://wordpress.org/support/view/plugin-reviews/my-loginlogout" target="_blank" >Please  Rate this plugin </a>
    <br/>
</div>
<div style="color:green"><span style="font-weight:bold;">Note:</span> deactivate the plugin when you want to add new menu-location or changing the theme 
</div>
</div>











