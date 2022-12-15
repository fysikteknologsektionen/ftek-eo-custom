<?php
/*
Plugin Name: Event Organiser Pro Customisation
Description: Customises Event Organiser Pro
Version: 1.0.5
Author: Johan Winther (johwin)
Text Domain: ftek_eo_custom
Domain Path: /languages
GitHub Plugin URI: fysikteknologsektionen/ftek-eo-custom
Primary Branch: main
*/


/**
* Use first and last name in export instead of display name
*/
add_filter( 'eventorganiser_export_bookings_headers', 'ftek_eo_custom_csv_headers' );
function ftek_eo_custom_csv_headers( $headers ){
    
    //The plug-in will automatically recognise the bookee_fname & bookee_lname columns, when enabled
    $headers['bookee_fname'] = __( 'Bookee (First name)', 'eventorganiserp' );
    $headers['bookee_lname'] = __( 'Bookee (Second name)', 'eventorganiserp' );
    $headers['ticket']       = __( 'Ticket', 'eventorganiserp' ); // setup in the filter "eventorganiser_export_bookings_cell_ticket" below

    unset( $headers['bookee'] );
    unset( $headers['booking_ticket_qty'] );
    unset( $headers['occurrence'] );
    
    array_reorder_keys($headers, 'event,booking_date,booking_ref,booking_status,bookee_fname,bookee_lname,bookee_email,ticket,booking_total_price,meta_6,booking_notes');
    
    return $headers;   
}
// Show ticket type in booking export
add_filter( 'eventorganiser_export_bookings_cell_ticket', function( $cell, $booking, $export ) {
    $tickets = eo_get_booking_tickets( $booking->ID, false );
    $cell    = '';
        if( $tickets ){
            $ticket_names = wp_list_pluck( $tickets, 'ticket_name' );
            $cell = implode( ', ', $ticket_names );
        }       
    return $cell;
}, 10, 3 );


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
    
    // $tickets is an array ( ticket type ID => quantity purchased ). 
    // Remove ticket types that have not been selected (0 quantity)
    $tickets = array_filter( $tickets );
    
    // Throw error if user has previously made a booking for this event
    if( eo_user_has_bookings( get_current_user_id(),  $post_id, $occurrence_id, eo_get_reserved_booking_statuses() ) ){
        //This error will prevent the booking from being processed, and display the given error message
        $errors->add( 'previous_booking', 'Du har redan gjort en bokning för detta evenemang.' );
    }
    
    // Count how many tickets are in this booking
    $total_qty = 0;
    if ( $tickets ) {
        $total_qty = array_sum( $tickets );
    }
    
    // Throw error if booking contains multiple tickets
    if( $total_qty > 1 ){
        $errors->add( 'too_many_tickets', 'Du kan bara köpa en biljett.' );
    }
    
    return $input;
}
add_action( 'eventorganiser_validate_booking_submission', 'ftek_eo_custom_limit_tickets_in_booking', 20, 3 );


/**
* function array_reorder_keys
* reorder the keys of an array in order of specified keynames; all other nodes not in $keynames will come after last $keyname, in normal array order
* @param array &$array - the array to reorder
* @param mixed $keynames - a csv or array of keynames, in the order that keys should be reordered
*/
function array_reorder_keys(&$array, $keynames){
    if(empty($array) || !is_array($array) || empty($keynames)) return;
    if(!is_array($keynames)) $keynames = explode(',',$keynames);
    if(!empty($keynames)) $keynames = array_reverse($keynames);
    foreach($keynames as $n){
        if(array_key_exists($n, $array)){
            $newarray = array($n=>$array[$n]); //copy the node before unsetting
            unset($array[$n]); //remove the node
            $array = $newarray + array_filter($array); //combine copy with filtered array
        }
    }
}

?>
