<?php
/*
Plugin Name: Event Organiser Pro Customisation
Description: Customises Event Organiser Pro
Version: 1.0.1
Author: Johan Winther (johwin)
Text Domain: ftek_eo_custom
Domain Path: /languages
GitHub Plugin URI: Fysikteknologsektionen/ftek-eo-custom
*/

// Is Event Organiser Pro installed?
if( is_plugin_active( 'event-organiser-pro/event-organiser-pro.php') {
    
    /**
    * Use first and last name in export instead of display name
    */
    add_filter( 'eventorganiser_export_bookings_headers', 'ftek_eo_custom_csv_headers' );
    function ftek_eo_custom_csv_headers( $headers ){
        
        //The plug-in will auomatically recognise the bookee_fname & bookee_lname columns, when enabled
        $headers['bookee_fname'] = __( 'Bookee (First name)', 'eventorganiserp' );
        $headers['bookee_lname'] = __( 'Bookee (Second name)', 'eventorganiserp' );
        unset( $headers['bookee'] ); //Remove default, 'Bookee' column
        
        return $headers;   
    }
    
    
    /**
    * Hides the booking form if user has already made a booking for this event
    *
    * If you want them to be able to book several dates for the same event - remove this 
    */
    add_action( 'wp_head', 'ftek_eo_custom_hide_if_booked' );
    function ftek_eo_custom_hide_if_booked(){
        
        if( is_singular( 'event' ) && eo_user_has_bookings( get_current_user_id(),  get_the_ID(), null, eo_get_reserved_booking_statuses() ) ){
            remove_filter( 'the_content', 'eventorganiser_display_booking_table', 999 );
        }
    }
    
    
    /**
    * Limits bookees to one booking per event
    * Limits bookings to one ticket
    *
    * @param array Data posted form the booking form
    * @param object Booking form object
    * @param WP_Error Error object to add any errors to
    */
    function ftek_eo_custom_limit_tickets_in_booking( $input, $form, $errors ){
        
        $input = $input + array( 'event_id' => 0, 'occurrence_id' => 0, 'gateway' => false, 'tickets'=>array() );
        $tickets = $input['tickets'];
        $post_id = $input['event_id'];
        $occurrence_id = $input['occurrence_id'];
        
        /* $tickets is an array ( ticket type ID => quantity purchased ). 
        Remove ticket types that have not been selected (0 quantity) */
        $tickets = array_filter( $tickets );
        
        /* Throw error if user has previously made a booking for this event */
        if( eo_user_has_bookings( get_current_user_id(),  $post_id, $occurrence_id, eo_get_reserved_booking_statuses() ) ){
            //This error will prevent the booking from being processed, and display the given error message
            $errors->add( 'previous_booking', 'Du har redan gjort en bokning för detta evenemang.' );
        }
        
        /* Count how many tickets are in this booking */
        $total_qty =0;
        if ( $tickets ) {
            $total_qty = array_sum( $tickets );
        }
        
        /* Throw error if booking contains multiple tickets */
        if( $total_qty > 1 ){
            $errors->add( 'too_many_tickets', 'Du kan bara köpa en biljett.' );
        }
        
        return $input;
    }
    add_action( 'eventorganiser_validate_booking_submission', 'ftek_eo_custom_limit_tickets_in_booking', 20, 3 );
    
}

?>