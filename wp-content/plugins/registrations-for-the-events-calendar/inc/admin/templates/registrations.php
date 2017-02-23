<h1><?php _e( 'Overview', 'registrations-for-the-events-calendar' ); ?></h1>
<?php if ( ! isset( $options['default_max_registrations'] ) ) : ?>
    <div class="notice notice-info is-dismissible">
        <p>
            <?php esc_attr_e( 'Hey! First time using the plugin? You can start configuring on the' , 'registrations-for-the-events-calendar' ); ?>
            <a href="edit.php?post_type=tribe_events&page=registrations-for-the-events-calendar%2F_settings&tab=form">"Form" tab</a><br />
            <?php esc_attr_e( 'Or check out our setup directions' , 'registrations-for-the-events-calendar' ); ?>
            <a href="http://roundupwp.com/products/registrations-for-the-events-calendar/setup/" target="_blank">on our website</a>
        </p>
    </div>
<?php endif; ?>

<?php if ( empty( $tz_offset )) : ?>
<form method="post" action="options.php">
    <?php settings_fields( 'rtec_options' ); ?>
    <?php do_settings_sections( 'rtec_timezone' ); ?>
    <input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" /><br />
    <hr>
</form>
<?php endif; ?>

<div class="rtec-wrapper rtec-overview">
<?php
$db = new RTEC_Db_Admin();
global $rtec_options;

$offset = isset( $_GET['offset'] ) ? (int)$_GET['offset'] : 0;
$posts_per_page = 20;

$events = array();
$events = tribe_get_events( array(
    'posts_per_page' => $posts_per_page,
    'start_date' => date( '2000-1-1 0:0:0' ),
    'orderby' => 'date',
    'order' => 'DESC',
	'offset' => $offset
) );

$event_ids_on_page = array();
$columns = rtec_get_current_columns( 3 );
$fields = array( 'registration_date' );
foreach ( $columns as $key => $value ) {
	$fields[] = $key;
}
$fields[] = 'status';

foreach ( $events as $event ) :

	// used to update new vs current registrations in db
	$event_ids_on_page[] = $event->ID;

	$data = array(
		'fields' => $fields,
		'id' => $event->ID,
		'order_by' => 'registration_date'
	);
    $registrations = $db->retrieve_entries( $data );

    // set post meta
    $meta = get_post_meta( $event->ID );

    $event_meta['post_id'] = $event->ID;
    $event_meta['title'] = $event->post_title;
    $event_meta['start_date'] = date_i18n( 'F jS, g:i a', strtotime( $meta['_EventStartDate'][0] ) );
    $event_meta['end_date'] = date_i18n( 'F jS, g:i a', strtotime( $meta['_EventEndDate'][0] ) );
	$default_disabled = isset( $rtec_options['disable_by_default'] ) ? $rtec_options['disable_by_default'] : false;
	$event_meta['disabled'] = isset( $meta['_RTECregistrationsDisabled'][0] ) ? $meta['_RTECregistrationsDisabled'][0] : $default_disabled;

    // set venue meta
    $venue_meta = isset( $meta['_EventVenueID'][0] ) ? get_post_meta( $meta['_EventVenueID'][0] ) : array();
	$venue = rtec_get_venue( $event->ID );
	$event_meta['venue_title'] = ! empty( $venue ) ? $venue : '(no location)';
?>
    
    <div class="rtec-single-event">
    
        <div class="rtec-event-meta">
            <a href="<?php echo esc_url( 'edit.php?post_type=tribe_events&page=registrations-for-the-events-calendar%2F_settings&tab=single&id=' . $event->ID ); ?>"><h3><?php echo esc_html( $event_meta['title'] ); ?></h3></a>
            <p><?php echo esc_html( $event_meta['start_date'] ); ?> to <?php echo esc_html( $event_meta['end_date'] ); ?></p>
            <p><?php echo esc_html( $event_meta['venue_title'] ); ?></p>
        </div>
	    <div class="rtec-event-options postbox closed">
		    <button type="button" class="handlediv button-link" aria-expanded="false"><span class="screen-reader-text"><?php _e( 'Toggle panel: Information', 'registrations-for-the-events-calendar' ); ?></span><span class="toggle-indicator" aria-hidden="true"></span></button>
		    <span class="hndle"><span><?php _e( 'Event Options', 'registrations-for-the-events-calendar' ); ?></span></span>
	    </div>
	    <div class="rtec-event-options rtec-hidden-options postbox">
		    <form class="rtec-event-options-form" action="">
			    <input type="hidden" name="rtec_event_id" value="<?php echo esc_attr( $event_meta['post_id'] ); ?>" />
			    <input type="hidden" name="rtec_checkboxes" value="_RTECregistrationsDisabled" />
			    <input type="checkbox" id="rtec-disable-<?php echo esc_attr( $event_meta['post_id'] ); ?>" name="_RTECregistrationsDisabled" <?php if( $event_meta['disabled'] == '1' ) { echo 'checked'; } ?> value="1"/>
			    <label for="rtec-disable-<?php echo esc_attr( $event_meta['post_id'] ); ?>"><?php _e( 'Disable registrations for this event', 'registrations-for-the-events-calendar' ); ?></label>
			    <div class="rtec-clear"></div>
			    <button class="button action rtec-admin-secondary-button rtec-update-event-options"><?php _e( 'Update', 'registrations-for-the-events-calendar'  ); ?></button>
			    <div class="rtec-clear"></div>
		    </form>
	    </div>
        <table class="widefat rtec-registrations-data">
            <thead>
                <tr>
                    <th><?php _e( 'Registration Date', 'registrations-for-the-events-calendar' ) ?></th>
                <?php foreach ( $columns as $column ) : ?>
                    <th><?php echo esc_html( $column ); ?></th>
                <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php if ( ! empty( $registrations ) ) : ?>
    
            <?php foreach( $registrations as $registration ): ?>
                <tr>
                    <td class="rtec-first-data">
                        <?php if ( $registration['status'] == 'n' ) {
                            echo '<span class="rtec-notice-new">' . _( 'new' ) . '</span>';
                        }
                        echo esc_html( date_i18n( 'm/d g:i a', strtotime( $registration['registration_date'] ) + $tz_offset ) ); ?>
                    </td>
	                <?php foreach ( $columns as $key => $value ) : ?>
		                <td><?php
			                if ( isset( $registration[$key] ) ) {
				                echo esc_html( str_replace( '\\', '', $registration[$key] ) );
			                } else if ( isset( $registration[$key.'_name'] ) ) {
				                echo esc_html( str_replace( '\\', '', $registration[$key] ) );
			                } else if ( isset( $registration['custom'][$value] ) ) {
				                echo esc_html( str_replace( '\\', '', $registration['custom'][$value] ) );
			                }
			                ?></td>
	                <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
    
            <?php else: ?>
    
                <tr>
                    <td colspan="4" align="center"><?php _e( 'No Registrations Yet', 'registrations-for-the-events-calendar' ); ?></td>
                </tr>
    
            <?php endif; // registrations not empty?>
    
            </tbody>
        </table>
	    <div class="rtec-event-actions rtec-clear">
	        <a href="<?php echo esc_url( 'edit.php?post_type=tribe_events&page=registrations-for-the-events-calendar%2F_settings&tab=single&id=' . $event->ID ); ?>" class="rtec-admin-secondary-button button action"><?php _e( 'Detailed View', 'registrations-for-the-events-calendar' ); ?></a>
	    </div>
    </div> <!-- rtec-single-event -->

<?php endforeach; // end loop ?>
	<div class="rtec-clear">
	<?php if ( $offset > 0 ) : ?>
		<a href="<?php echo esc_url( 'edit.php?post_type=tribe_events&page=registrations-for-the-events-calendar%2F_settings&tab=registrations&offset=' . ( $offset - $posts_per_page ) ); ?>" class="rtec-primary-button"><?php _e( 'Previous Events', 'registrations-for-the-events-calendar' ); ?></a>
	<?php endif; ?>

	<?php if ( ! empty( $events ) ) : ?>
		<a href="<?php echo esc_url( 'edit.php?post_type=tribe_events&page=registrations-for-the-events-calendar%2F_settings&tab=registrations&offset=' . ( $offset + $posts_per_page ) ); ?>" class="rtec-primary-button rtec-next"><?php _e( 'Next Events', 'registrations-for-the-events-calendar' ); ?></a>
	<?php else : ?>
		<p><?php _e( 'No more events to display', 'registrations-for-the-events-calendar' ); ?></p>
	<?php endif; ?>
	</div>
</div> <!-- rtec-wrapper -->

<?php $db->update_statuses( $event_ids_on_page );