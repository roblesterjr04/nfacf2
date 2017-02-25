var myloginlogout = jQuery.noConflict();
myloginlogout(document).ready(function(){

myloginlogout("#show_custom_login_button").click(function(){
	myloginlogout( "#login-dropdown" ).hide(1000);	
	myloginlogout( "#custom_loginid" ).show(1000);
	myloginlogout( "#show_custom_login_button" ).hide(1000);

});

myloginlogout("#hide_custom_login_button").click(function(){
	myloginlogout( "#custom_loginid" ).hide( 1000);
	myloginlogout( "#custom_login_url" ).val("");
	myloginlogout( "#login-dropdown" ).show(1000);
	myloginlogout( "#show_custom_login_button" ).show(1000);//button

});
});
