<?php
/*
Plugin Name: Smart Contact Form
Description: The Smart Contact Form is a lightweight plugin that uses a unique screening process to stop spam from being sent to you without relying on captcha systems. It uses your theme's styles and is easy to customize with CSS.
Version: 1.0.0
License: GPLv2
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
Author: Making The Impact LLC
Author URI: https://makingtheimpact.com
Text Domain: smart-contact-form
*/

/* !0. TABLE OF CONTENTS */
/*
1. HOOKS
    1.1 - Shortcode Hook
    1.2 - Plugin Menu Hook
    1.3 - Add Custom Columns to Message Post Type
    1.4 - Custom Admin Titles Add hook to call the function for custom titles
2. SHORTCODES
    2.1 - Register Shortcodes
3. FILTERS
4. EXTERNAL SCRIPTS
    4.1 - Include the Easy Data Handling Plugin
5. ACTIONS
    5.1 - Enqueue Styles and Scripts Action
6. HELPERS
7. CUSTOM POST TYPES
    7.1 - Custom Post Type Messages
    7.2 - Advanced Custom Fields
    7.3 - Local JSON for Custom Fields
8. ADMIN PAGES
    8.1 - Mailbox Menu
    8.2 - Register Options Page
    8.3 - Custom Admin Titles and Columns
9. SETTINGS
    9.1 - Register Plugin Settings Options
10. STYLES
    10.1 - Enqueue Styles and Scripts
*/



/* !1. HOOKS */

// 1.1 - Shortcode Hook
add_action('init', 'smart_register_shortcode_function');

// 1.2 - Plugin Menu Hook
add_action('admin_menu', 'scf_menu', 9);

// 1.3 - Add Custom Columns to Message Post Type
add_filter('manage_scf_messages_posts_columns', 'custom_scf_messages_columns');
add_action('manage_scf_messages_posts_custom_column', 'custom_scf_messages_column_data',10,2);

// 1.4 - Custom Admin Titles Add hook to call the function for custom titles
add_action('admin_head-edit.php','scf_register_custom_admin_titles');

// 1.5 - Plugin Settings Options
add_action( 'admin_init', 'smart_register_settings' );

// 1.6 - Smart Admin Styles
add_action('admin_head', 'scf_forms_admin_styles');

/* !2. SHORTCODES */

// 2.1 - Register Shortcodes
function smart_register_shortcode_function() {
  add_shortcode('smart_contact_form', 'scf_smart_contact_form');
}

// 2.2 - Smart Form Shortcode Function
/*
  This shortcode displays the simple contact form on the website, processes submission, saves the responses to the custom post type, and sends an email to the user and website admin.
*/
function scf_smart_contact_form() {
  // Load the CSS and JS scripts
  scf_load_scripts();

  $scf_name = '';
  $scf_email = '';
  $scf_phone = '';
  $scf_subject = '';
  $scf_message = '';
  $display_message = '';

  $scf_errors = '';
  $scf_confirm = '';

  $scf_name_error = '';
  $scf_email_error = '';
  $scf_phone_error = '';
  $scf_subject_error = '';
  $scf_message_error = '';

  $scf_input_name_error = '';
  $scf_input_email_error = '';
  $scf_input_phone_error = '';
  $scf_input_subject_error = '';
  $scf_input_message_error = '';

  $scf_display_mode = 0;

  $scf_subjects = sanitize_textarea_field(get_option('scf_subjects'));
  if ($scf_subjects != ''):
    $scf_subjects_list = explode(',', $scf_subjects);
  else:
    $scf_subjects_list = array(
      'General Inquiry',
      'Sales Issue',
      'Technical Issue',
      'Question or Comment'
    );
  endif;

  // Form Processing

  // if a form was submitted, ensure it was the contact form before processing
  if ($_SERVER['REQUEST_METHOD'] == 'POST'):
    if (isset($_POST['scf_submit'])):
      $scf_form_submit = $_POST['scf_submit'];
    else:
      $scf_form_submit = false;
    endif;
    if (isset($_POST['scf_confirm'])):
      $scf_form_confirm = $_POST['scf_confirm'];
    else:
      $scf_form_confirm = false;
    endif;
  else:
    $scf_form_submit = false;
    $scf_form_confirm = false;
  endif;

  if ($_SERVER['REQUEST_METHOD'] == 'POST' && $scf_form_submit == true):
    $scf_name = $_POST['scf_sf_field1'];
    $scf_email = $_POST['scf_sf_field2'];
    $scf_phone = $_POST['scf_sf_field3'];
    $scf_subject = $_POST['scf_sf_field4'];
    $scf_message = $_POST['scf_sf_field5'];

    // Prepares message value for editing - removes slashes
    $sanitized_message = edh_sanitize($scf_message, 'multitext');
    $display_message = edh_display_text($sanitized_message, 1);

    // Required Fields
    if ($scf_name == ''):
      $scf_name_error = '<div class="scf-error">Please provide your name.</div>';
    endif;
    if ($scf_email == ''):
      $scf_email_error = '<div class="scf-error">Please provide your email address.</div>';
    endif;
    if ($scf_subject == ''):
      $scf_subject_error = '<div class="scf-error">Please select the subject of your message.</div>';
    endif;
    if ($scf_message == ''):
      $scf_message_error = '<div class="scf-error">Please type in your message below.</div>';
    endif;

    // Validation
    if (!edh_validate($scf_name, 'letters1a') && $scf_name != ''):
      $scf_name_error = '<div class="scf-error">The name you have provided contains invalid characters.</div>';
    endif;
    if (!edh_validate($scf_email, 'email') && $scf_email != ''):
      $scf_email_error = '<div class="scf-error">The email address you have provided is not in a valid format.</div>';
    endif;
    if (!edh_validate($scf_phone, 'intphone') && $scf_phone != ''):
      $scf_phone_error = '<div class="scf-error">The phone number you provided has invalid characters.</div>';
    endif;
    if (!edh_validate($scf_subject, 'letters1a') && $scf_subject != ''):
      $scf_subject_error = '<div class="scf-error">Please select a valid subject from the dropdown provided.</div>';
    endif;
    if (!edh_validate($scf_message, 'multitext') && $scf_message != ''):
      $scf_message_error = '<div class="scf-error">The message you have entered below contains invalid characters.</div>';
    endif;

    if ($scf_name_error != ''):
      $scf_input_name_error = ' class="scf-input-error" ';
    endif;
    if ($scf_email_error != ''):
      $scf_input_email_error = ' class="scf-input-error" ';
    endif;
    if ($scf_phone_error != ''):
      $scf_input_phone_error = ' class="scf-input-error" ';
    endif;
    if ($scf_subject_error != ''):
      $scf_input_subject_error = ' class="scf-input-error" ';
    endif;
    if ($scf_message_error != ''):
      $scf_input_message_error = ' class="scf-input-error" ';
    endif;

    // If there are no errors....
    if ($scf_name_error == '' && $scf_email_error == '' && $scf_phone_error == '' && $scf_subject_error == '' && $scf_message_error == ''):

      // Sanitize values - they are safe for storing in database now
      $sanitized_name = edh_sanitize($scf_name, 'letters1a');
      $sanitized_email = edh_sanitize($scf_email, 'email');
      $sanitized_phone = edh_sanitize($scf_phone, 'intphone');
      $sanitized_subject = sanitize_textarea_field($scf_subject);
      $sanitized_message = edh_sanitize($scf_message, 'multitext');

      $scf_subject_value = '';

      $subject_valid = false; // verify subject is a valid option, default to general
      foreach($scf_subjects_list as $scfl):
        if ($scfl == $sanitized_subject):
          $scf_subject_value = $scfl; 
          $subject_valid = true; 
        endif; 
      endforeach; 
      if ($subject_valid === false):
        $scf_subject_value = "General Inquiry";
      endif; 

      // Check message for spam
      $scf_prepare_message = edh_display_text($sanitized_message, 1);
      $scf_flagged_words = scf_check_for_spam($scf_prepare_message);

      if (is_array($scf_flagged_words)):
        $spam_score = count($scf_flagged_words); // count array
      else:
        $spam_score = 0;
      endif;

      // Is the message spam? If so, perform human check, otherwise save and send the message
      if ($spam_score > 0):
        // Check if Human
        $scf_display_mode = 1;
      else:
        // Message is not spam so...
        $scf_is_spam = false;

        // Save the message
        $scf_save_confirm = scf_save_message($sanitized_name, $sanitized_email, $sanitized_phone, $scf_subject_value, $sanitized_message, $scf_is_spam);

        // Send the email
          // remove slashes from message
          $pretty_message = wp_unslash($sanitized_message);
        $scf_send_confirm = scf_send_mail($sanitized_name, $sanitized_email, $sanitized_phone, $scf_subject_value, $pretty_message);

        // If it was saved and sent successfully...
        if ($scf_save_confirm && $scf_send_confirm):
          $scf_redirect = edh_sanitize(get_option('scf_url'), 'url'); 
          if ($scf_redirect != ''):
            wp_redirect($scf_redirect);
            exit;
          else: // the redirect is not set
            // get the success message 
            $scf_success = sanitize_textarea_field(get_option('scf_success'));
            // if the success message is set 
            if ($scf_success != ''):
              $scf_mail_confirmation = $scf_success;
            else: // the success message is NOT set so use default 
              $scf_mail_confirmation = 'Your message was sent successfully.';
            endif; // end of if the success message is set 
          endif; // if the redirect url is set          
          
        else: // there was an error sending the email 
          $scf_mail_confirmation = 'We encountered a problem while sending your message. Please try again later.';
        endif; // end of if the message was sent successfully

        // Display Confirmation
        $scf_display_mode = 2;

      endif; // end of spam score

    else:
      $scf_errors = '<div class="scf-error"><h4>Please correct the issues below.</h4></div>';
    endif;

  endif;

  if ($_SERVER['REQUEST_METHOD'] == 'POST' && $scf_form_confirm == true):
    $scf_name = $_POST['scf_confirm_field1'];
    $scf_email = $_POST['scf_confirm_field2'];
    $scf_phone = $_POST['scf_confirm_field3'];
    $scf_subject = $_POST['scf_confirm_field4'];
    $scf_message = $_POST['scf_confirm_field5'];
    $scf_confirm_value = $_POST['scf_confirm_field6'];

    // Trim confirm value
    $scf_confirm_value_trimmed = substr($scf_confirm_value, 9);

    // Make sure the value only has numbers
    $sanitized_confirm_value = edh_sanitize($scf_confirm_value_trimmed, 'num');
    if ($sanitized_confirm_value == '' || $sanitized_confirm_value == null):
      $sanitized_confirm_value = 0;
    endif;

    // Sanitize values - they are safe for storing in database now
    $sanitized_name = edh_sanitize($scf_name, 'letters1a');
    $sanitized_email = edh_sanitize($scf_email, 'email');
    $sanitized_phone = edh_sanitize($scf_phone, 'intphone');
    $scf_subject_value = edh_sanitize($scf_subject, 'text');
    $sanitized_message = edh_sanitize($scf_message, 'multitext');

    // if required values are not blank
    if ($sanitized_name != '' && $sanitized_email != '' && $scf_subject_value != '' && $sanitized_message != ''):

      $scf_current = time();
      // Calculate the difference
      $scf_difference = $scf_current - $sanitized_confirm_value;

      // Is it less than an hour and greater than 0?
      if ($scf_difference > 0 && $scf_difference < 3600):

        $scf_is_spam = false;
        $scf_save_confirm = false;
        $scf_send_confirm = false;

        // Prepare message for final check
        $scf_final_check_message = strtolower(edh_display_text($sanitized_message, 1));

        // Check if message has http or www. if it does consider it spam
        if (strpos($scf_final_check_message, 'http') !== false || strpos($scf_final_check_message, 'www.') !== false):
          $scf_is_spam = true;
        endif;

        // If the time difference is too small, it is probably a bot
        if ($scf_difference < 2):
          $scf_is_spam = true;
        endif;

        // Check spam score
        $scf_flagged_words = scf_check_for_spam($scf_final_check_message);
        if (is_array($scf_flagged_words)):
          $spam_score = count($scf_flagged_words); // count array
          if ($spam_score > 3):
            $scf_is_spam = true;
          endif;
        endif; 

        // If spam score is < 4 AND message does not contain http or www AND the confirm time is greater than 1 second, send email
        if (!$scf_is_spam):
          // Send the email
            // remove slashes from message
            $pretty_message = wp_unslash($sanitized_message);
          $scf_send_confirm = scf_send_mail($sanitized_name, $sanitized_email, $sanitized_phone, $scf_subject_value, $pretty_message);
        endif;

        // Save the message
        $scf_save_confirm = scf_save_message($sanitized_name, $sanitized_email, $sanitized_phone, $scf_subject_value, $sanitized_message, $scf_is_spam);

        // If it was saved and sent successfully...
        if ($scf_save_confirm && $scf_send_confirm):
          $scf_mail_confirmation = 'Your message was sent successfully.';
        elseif($scf_save_confirm && !$scf_send_confirm):
          $scf_mail_confirmation = 'We could not send your message but a copy of it has been stored in the system.';
        else:
          $scf_mail_confirmation = 'We encountered a problem while trying to send your message. Please try again later.';
        endif;

        // Display Confirmation
        $scf_display_mode = 2;

      else:
        // The message has an invalid time value and could be spam
        $scf_mail_confirmation = 'The form timed out and your message could not be sent. Please try again.';
        $scf_display_mode = 2;
      endif;
    else:
      // The required fields are empty so the message can't be sent
      $scf_mail_confirmation = 'We encountered a problem while sending your message. Please try again later.';
      $scf_display_mode = 2;
    endif;

  endif;

  // Get Current Page URL
  $curr_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; // url without query string
  $curr_url = esc_url($curr_url); // clean URL

  // Get the subject field options
  

  // Display Contact Form
  if ($scf_display_mode == 0):
    $output = '<div class="scf-simple-form-container"><form action="' . $curr_url . '" id="scf_simple_form" name="scf_simple_form" class="scf-form" method="post">
      <input type="hidden" name="scf_submit" value="true">' . $scf_errors . '
      <div class="scf-input-container">
        <div class="scf-input-field">
          <label for="scf_sf_field1">Name:</label>
          ' . $scf_name_error . '<input type="text" id="scf_sf_field1" name="scf_sf_field1" value="' . $scf_name . '" autocomplete="off"' . $scf_input_name_error . '>
        </div>
      </div>
      <div class="scf-input-container">
        <div class="scf-input-field">
          <label for="scf_sf_field2">Email:</label>' . $scf_email_error . '
          <input type="email" id="scf_sf_field2" name="scf_sf_field2" value="' . $scf_email . '" autocomplete="off"' . $scf_input_email_error . '>
        </div>
      </div>
      <div class="scf-input-container">
        <div class="scf-input-field">
          <label for="scf_sf_field3">Phone:</label>' . $scf_phone_error . '
          <input type="tel" id="scf_sf_field3" name="scf_sf_field3" value="' . $scf_phone . '" autocomplete="off"' . $scf_input_phone_error . '>
        </div>
      </div>
      <div class="scf-input-container">
        <div class="scf-input-field">
          <label for="scf_sf_field4">Subject:</label>' . $scf_subject_error . '<select id="scf_sf_field4" name="scf_sf_field4" autocomplete="off"' . $scf_input_subject_error . '>
            ';
            foreach ($scf_subjects_list as $subject):
              $output .= '<option value="' . $subject . '"';
              if ($scf_subject == $subject):
                $output .= ' selected';
              endif;
              $output .= '>' . $subject . '</option>';
            endforeach;
          $output .= '
          </select>
        </div>
      </div>
      <div class="scf-input-container">
        <div class="scf-input-field">
          <label for="scf_sf_field5">Message:</label>' . $scf_message_error . '
          <textarea id="scf_sf_field5" name="scf_sf_field5" autocomplete="off"' . $scf_input_message_error . '>' . $display_message . '</textarea>
        </div>
      </div>
      <div class="scf-input-container-submit">
        <div class="scf-input-field-submit">
          <input type="submit" name="scf_sf_save" value="Send Message" />
        </div>
      </div>
    </form></div>';
  endif;

  // If the message did not pass the spam check, display the extra step
  if ($scf_display_mode == 1):
    $scf_start_time = time();
    $scf_secure_string = edh_generate_password() . '-' . $scf_start_time;
    $output = '<div class="scf-simple-form-container scf-simple-form-confirm"><form action="' . $curr_url . '" id="scf_simple_form" name="scf_simple_form" class="scf-form" method="post">
      <input type="hidden" name="scf_confirm" value="true">
      <input type="hidden" name="scf_confirm_field1" value="' . $sanitized_name . '">
      <input type="hidden" name="scf_confirm_field2" value="' . $sanitized_email . '">
      <input type="hidden" name="scf_confirm_field3" value="' . $sanitized_phone . '">
      <input type="hidden" name="scf_confirm_field4" value="' . $scf_subject_value . '">
      <input type="hidden" name="scf_confirm_field5" value="' . $sanitized_message . '">
      <input type="hidden" name="scf_confirm_field6" value="' . $scf_secure_string . '">
      ' . $scf_errors . '
      <h3 class="scf-confirm-header">Before we send your message, please confirm you are a human by clicking the button below.</h3>
      <div class="scf-input-container-submit-confirm">
        <div class="scf-input-field-submit-confirm">
          <input type="submit" name="scf_sf_confirm" value="Send Message" />
        </div>
      </div>
    </div>';
  endif;

  // If the message passed spam check and was sent, display confirmation message
  if ($scf_display_mode == 2):
    $output = '<div class="scf-simple-form-container">
      <p class="scf-confirmation-message">' . $scf_mail_confirmation . '</p>
    </div>';
  endif;

  return $output;
}

/* !3. FILTERS */

/* !4. EXTERNAL SCRIPTS */

// 4.1 - Include the Easy Data Handling Plugin
if (!function_exists('edh_sanitize')) {
  $easy_data_handler_path = plugin_dir_path( __FILE__ ) . 'includes/easy-data-handling.php';
  if(file_exists($easy_data_handler_path)){
    include $easy_data_handler_path;
  }
}

/* !5. ACTIONS */

// 5.1 - Enqueue Styles and Scripts
add_action('wp_enqueue_scripts', 'scf_add_styles_scripts');

/* !6. HELPERS */

// 6.1 - Check for Spam Keywords
function scf_check_for_spam($string) {
  $flagged_word_list = array();

  // Load Spam Word List
  $scf_spam_keyword_file = plugin_dir_path( __FILE__ ) . 'tools/keywordlist.php';
  if(file_exists($scf_spam_keyword_file)):
    include $scf_spam_keyword_file;
    // Make string lowercase for comparison
    $check_spam_message = strtolower($string);
    // Check for spam words
    foreach ($scf_spam_keyword_list as $scf_spam_word):
      // Compare string with each common spam word
      if (strpos($check_spam_message, strtolower($scf_spam_word)) !== false):
        array_push($flagged_word_list, $scf_spam_word);
      endif;
    endforeach;
    return $flagged_word_list;
  else: // spam keyword list not found 
    return false; 
  endif; // end of if spam keyword list file exists   
}

// 6.2 - Save Message
function scf_save_message($name, $email, $phone, $selsubject, $message, $spam){
  $status = false;

  // Create new post of scf_message type
  $scf_new_message = array(
    'post_type' => 'scf_messages',
    'post_status' => 'publish',
  );
  $scf_new_message_id = wp_insert_post( $scf_new_message );

  if ($scf_new_message_id > 0 && !is_wp_error($scf_new_message_id)):

    // Save all the form values
    update_field('scf_name', $name, $scf_new_message_id);
    update_field('scf_email_address', $email, $scf_new_message_id);
    update_field('scf_phone_number', $phone, $scf_new_message_id);
    update_field('scf_subject', $selsubject, $scf_new_message_id);
    update_field('scf_message', $message, $scf_new_message_id);
    update_field('scf_spam', $spam, $scf_new_message_id);

    $status = true; // Saved successfully

  endif;

  return $status;
}

// 6.3 - Send Mail
function scf_send_mail($name, $email, $phone, $msgsubject, $message){
  $status = false;

  // Prepare message to send
  $sitename = sanitize_text_field(get_option('blogname'));
  $subject = $msgsubject . ' - ' . $sitename;
  $body = '
    <p>You have received a message through the ' . $sitename . ' contact form.</p><br>
    <p>Name: ' . $name . '</p>
    <p>Email: ' . $email . '</p>
    <p>Phone: ' . $phone . '</p>
    <p>Subject: ' . $msgsubject . '</p>
    <p>Message:</p>
    <p>' . $message . '</p>
    <p>If you would like to respond to their message, reply to: ' . $email . '</p>
  ';
  $admin_email = edh_sanitize(get_option('admin_email'), 'email');
  $headers[] = 'Content-Type: text/html; charset=UTF-8';
  $headers[] = 'From: ' . $sitename . '<' . $admin_email . '>';

  // Send the email...

  // Get email address from plugin settings
  $sent_counter = 0;
  for($i = 1; $i < 6; $i++): // there are 5 email address options to check
    $set_option_name = 'scf_send_to' . $i; // set the option name
    $fetch_email = edh_sanitize(get_option($set_option_name), 'email'); // get the email address and sanitize it
    // If the option is not blank AND it is a valid email address, then send it
    if ($fetch_email != '' && edh_validate($fetch_email, 'email')):
      $status = wp_mail( $fetch_email, $subject, $body, $headers ); // send email
      if ($status):
        $sent_counter++; // if the email was sent successfully, increment the counter by 1
      endif;
    endif;
  endfor;

  // If the no messages were sent successfully, send an email to owner of site instead
  if ($sent_counter == 0):
    $status = wp_mail( $admin_email, $subject, $body, $headers );
  endif;

  return $status;
}

/* !7. CUSTOM POST TYPES */

// 7.1 - Custom Post Type Messages
function cptui_register_my_cpts_scf_messages() {

	/**
	 * Post Type: Messages.
	 */

	$labels = array(
		"name" => __( "Messages", "customize-me-simple" ),
		"singular_name" => __( "Message", "customize-me-simple" ),
	);

	$args = array(
		"label" => __( "Messages", "customize-me-simple" ),
		"labels" => $labels,
		"description" => "",
		"public" => false,
		"publicly_queryable" => false,
		"show_ui" => true,
		"delete_with_user" => false,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"has_archive" => false,
		"show_in_menu" => "scf_forms",
		"show_in_nav_menus" => true,
		"exclude_from_search" => true,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => array( "slug" => "scf_messages", "with_front" => false ),
		"query_var" => true,
		"menu_position" => 5,
		"supports" => false,
	);

	register_post_type( "scf_messages", $args );
}

add_action( 'init', 'cptui_register_my_cpts_scf_messages' );

// 7.2 - Advanced Custom Fields
  // Define path and URL to the ACF plugin.
  define( 'MY_ACF_PATH', plugin_dir_path( __FILE__ ) . '/includes/acf/' );
  define( 'MY_ACF_URL', plugin_dir_url( __FILE__ ) . '/includes/acf/' );

  // Include the ACF plugin.
  include_once( MY_ACF_PATH . 'acf.php' );

  // Customize the url setting to fix incorrect asset URLs.
  add_filter('acf/settings/url', 'my_acf_settings_url');
  function my_acf_settings_url( $url ) {
      return MY_ACF_URL;
  }

  // (Optional) Hide the ACF admin menu item.
  /*add_filter('acf/settings/show_admin', 'my_acf_settings_show_admin');
  function my_acf_settings_show_admin( $show_admin ) {
      return false;
  }*/

// 7.3 - Advanced Custom Fields - Data
if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
	'key' => 'group_5cf9475f60a26',
	'title' => 'Contact Form Fields',
	'fields' => array(
		array(
			'key' => 'field_5cf94779dbbb8',
			'label' => 'Name',
			'name' => 'scf_name',
			'type' => 'text',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5cf9479fdbbba',
			'label' => 'Email Address',
			'name' => 'scf_email_address',
			'type' => 'email',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
		),
		array(
			'key' => 'field_5cf947b2dbbbb',
			'label' => 'Phone Number',
			'name' => 'scf_phone_number',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => 30,
		),
		array(
			'key' => 'field_5cf947fbdbbbc',
			'label' => 'Subject',
			'name' => 'scf_subject',
			'type' => 'text',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5cf94813dbbbd',
			'label' => 'Message',
			'name' => 'scf_message',
			'type' => 'textarea',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'maxlength' => '',
			'rows' => '',
			'new_lines' => '',
		),
		array(
			'key' => 'field_5cfed151c2360',
			'label' => 'Suspected Spam?',
			'name' => 'scf_spam',
			'type' => 'true_false',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'message' => '',
			'default_value' => 0,
			'ui' => 0,
			'ui_on_text' => '',
			'ui_off_text' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'scf_messages',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => array(
		0 => 'permalink',
		1 => 'the_content',
		2 => 'excerpt',
		3 => 'discussion',
		4 => 'comments',
		5 => 'revisions',
		6 => 'slug',
		7 => 'author',
		8 => 'format',
		9 => 'page_attributes',
		10 => 'featured_image',
		11 => 'categories',
		12 => 'tags',
		13 => 'send-trackbacks',
	),
	'active' => true,
	'description' => '',
));

endif;

// 7.3 - Local JSON for Custom Fields
/*add_filter('acf/settings/save_json', 'scf_acf_json_save_point');

function scf_acf_json_save_point( $path ) {
    // update path
    $path = plugin_dir_path( __FILE__ ) . '/includes/acf/local_json';
    // return
    return $path;
}*/


/* !8. ADMIN PAGES */

// 8.1 - Mailbox Menu
function scf_menu(){
  add_menu_page(
    'Mailbox Settings',
    'Mailbox',
    'manage_options',
    'scf_forms',
    'scf_forms_options_page',
    plugin_dir_url( __FILE__ ) . 'images/icon.png',
    3
  );
}

// 8.2 - Register Options Page
function scf_forms_options_page() {
  ?>
  <br/><img src="<?php echo plugin_dir_url( __FILE__ ); ?>images/logo.png" alt="Smart Contact Form Logo"/>
  <div>
    <div class="scf-column">
      <h2>Mailbox Settings</h2>
      <p>The Smart Contact Form is hard at work, protecting your contact form from the malicious spam attacks.</p>
      <h3>How It Works</h3>
      <p>When you place the contact form shortcode on the page, the special smart contact form will appear.</p>
      <p>When anyone uses the form it first validates each field and prompts the user to make any necessary corrections, then it screens the fields for words on the spam keyword list.</p>
      <p>Next, their message and details are scanned for spam words and given a rating. Any rating above 0 requires the user to take an extra step before the message is sent. This will make it more difficult for bots to get their message through.</p>
      <p>Messages with a spam score of 3 and above will not be sent, but will be stored.</p>
      <h3>How to Use smart</h3>
      <p>Just copy the shortcode below and paste it where you want the form to appear and we'll take care of the rest.</p>
      <p><b>Contact Form Shortcode:</b> [smart_contact_form]</p>
    </div>
    <div class="scf-column">
      <h3>Settings</h3>
      <p>You can specify the email addresses you would like messages to be sent to. If none are set, messages will be sent to the email address of the site admin.</p>
      <form method="post" action="options.php">
        <?php settings_fields( 'smart_options_group' ); ?>
        <table class="scf-settings scf-full-width">
          <tr valign="top">
            <th scope="row" width="25%"><label for="scf_send_to1">Email Addresses to Send To:</label></th>
          </tr>
          <tr>
            <td><input type="email" id="scf_send_to1" name="scf_send_to1" value="<?php echo get_option('scf_send_to1'); ?>" class="scf-full-width" autocomplete="off" /></td>
          </tr>
          <tr>
            <td><input type="email" id="scf_send_to2" name="scf_send_to2" value="<?php echo get_option('scf_send_to2'); ?>" class="scf-full-width" autocomplete="off" /></td>
          </tr>
          <tr>
            <td><input type="email" id="scf_send_to3" name="scf_send_to3" value="<?php echo get_option('scf_send_to3'); ?>" class="scf-full-width" autocomplete="off" /></td>
          </tr>
          <tr>
            <td><input type="email" id="scf_send_to4" name="scf_send_to4" value="<?php echo get_option('scf_send_to4'); ?>" class="scf-full-width" autocomplete="off" /></td>
          </tr>
          <tr>
            <td><input type="email" id="scf_send_to5" name="scf_send_to5" value="<?php echo get_option('scf_send_to5'); ?>" class="scf-full-width" autocomplete="off" /></td>
          </tr>
        </table><br>
        <p>List the subjects that you would like to have as options. Separate them with a comma. For example: General Inquiry,Sales Question,Technical Support</p>
        <textarea name="scf_subjects" rows="8" class="scf-full-width"><?php echo get_option('scf_subjects'); ?></textarea>
        <p>Set the message they see after their message has been successfully submitted.</p>
        <textarea name="scf_success" rows="4" class="scf-full-width"><?php echo get_option('scf_success'); ?></textarea>
        <p>Specify the URL that you would like the user to be taken to after submission.</p>
        <input type="text" id="scf_url" name="scf_url" class="scf-full-width" value="<?php echo get_option('scf_url'); ?>">        
        <?php  submit_button(); ?>
      </form>
    </div>
   </div>
  <?php
}
function scf_forms_admin_styles() {
  echo '<style>
    .scf-column {
      width: 100%;
      padding: 0px 10px;
    }
    .scf-settings th {
      text-align: left;
    }
    .scf-full-width {
      width: 95%;
      overflow: hidden;
    }
    @media screen and (min-width: 768px) {
      .scf-column {
        width: 45%;
        display: inline-block;
        float: left;
      }
    }
  </style>';
}

// 8.3 - Custom Admin Titles and Columns
function custom_scf_messages_columns($columns) {
  $columns = array(
    'cb' => '<input type="checkbox" />',
    'title' => __('Name','smart-contact-form'),
    'email' => __('Email','smart-contact-form'),
    'subject' => __('Subject','smart-contact-form'),
    'spam' => __('Suspected Spam?','smart-contact-form'),
    'date' => __('Date','smart-contact-form'),
  );
  return $columns;
}

// Add Custom Data to the Custom Post Types Columns
function custom_scf_messages_column_data($column, $post_id) {
  switch($column) {
    case 'email':
        $users_email = get_field('scf_email_address', $post_id);
        $output = edh_sanitize($users_email, 'email');
        break;
    case 'subject':
        $subject = get_field('scf_subject', $post_id);
        $output = edh_display_text(edh_sanitize($subject, 'text'), 0);
        break;
    case 'spam':
        $is_spam = get_field('scf_spam', $post_id);
        if ($is_spam == false):
          $output = 'No';
        else:
          $output = 'Yes';
        endif;
        break;
  }
  echo $output;
}

// Replace (no title) or Auto Draft - the fix for posts with no title
function scf_register_custom_admin_titles() {
  add_filter('the_title',
      'scf_custom_admin_titles',
      99,
      2
  );
}
function scf_custom_admin_titles($title, $post_id) {
  global $post;
  $output = $title;
  if(isset($post->post_type)):
    // Get the post type
    $post_type = $post->post_type;
    switch($post_type) {
      case 'scf_messages':
          $user_name = edh_sanitize(get_field('scf_name', $post_id), 'letters1a');
          $output = $user_name;
          break;
    }
  endif;
  return $output;
}


/* !9. SETTINGS */

// 9.1 - Register Plugin Settings Options
function smart_register_settings() {
  // email addresses to send to
  $args = array(
    'type' => 'string',
    'sanitize_callback' => 'sanitize_email',
    'default' => NULL,
  );
  register_setting( 'smart_options_group', 'scf_send_to1', $args );
  register_setting( 'smart_options_group', 'scf_send_to2', $args );
  register_setting( 'smart_options_group', 'scf_send_to3', $args );
  register_setting( 'smart_options_group', 'scf_send_to4', $args );
  register_setting( 'smart_options_group', 'scf_send_to5', $args );
  // subject lines and success message
  $args = array(
    'type' => 'string',
    'sanitize_callback' => 'sanitize_textarea_field',
    'default' => NULL,
  );
  register_setting( 'smart_options_group', 'scf_subjects', $args );
  register_setting( 'smart_options_group', 'scf_success', $args );
  // url 
  $args = array(
    'type' => 'string',
    'sanitize_callback' => 'esc_url_raw',
    'default' => NULL,
  );
  register_setting( 'smart_options_group', 'scf_url', $args );
}


/* !10. STYLES */

// 10.1 - Enqueue Styles and Scripts
function scf_add_styles_scripts() {
  wp_register_style('scf-styles', plugins_url('css/styles.css',__FILE__ ), array(), '1.00');
}

// Load them as needed
function scf_load_scripts() {
  // Load plugin CSS
  if( ! wp_style_is( "scf-styles", $list = 'enqueued' ) ) { wp_enqueue_style('scf-styles'); }
}



?>
