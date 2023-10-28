<?php
/*
Plugin Name: Messenger Chatbox
Description: Add a Messenger-like chatbox to your website.
Version: 1.0
Author: Your Name
*/


    $bot_chat_page_id = -1;
    $doc_chat_page_id = -1;

    //bootstrap
    add_action( 'wp_print_styles', 'add_bootstrap' );
    function add_bootstrap() {
        wp_register_style('prefix_bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css');
        wp_enqueue_style('prefix_bootstrap');
        wp_register_script('prefix_bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js');
        wp_enqueue_script('prefix_bootstrap');
    }



    //questionnarie post type creation
    function create_questionnaire_post_type() {
        $labels = array(
            'name' => 'Questionnaires',
            'singular_name' => 'Questionnaire',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Questionnaire',
            'edit_item' => 'Edit Questionnaire',
            'new_item' => 'New Questionnaire',
            'view_item' => 'View Questionnaire',
            'search_items' => 'Search Questionnaires',
            'not_found' => 'No Questionnaires found',
            'not_found_in_trash' => 'No Questionnaires found in Trash',
            'parent_item_colon' => '',
            'menu_name' => 'Questionnaires'
        );
    
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'questionnaires'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title'),
        );
    
        register_post_type('questionnaire', $args);
    }
    
    add_action('init', 'create_questionnaire_post_type');

    function custom_questionnaire_columns($columns) {
        unset($columns['title']);
        $columns['diseases'] = 'Diseases';
        $columns['symptoms'] = 'Symptoms';
        return $columns;
    }
    
    add_filter('manage_questionnaire_posts_columns', 'custom_questionnaire_columns');
    
    function display_questionnaire_column_data($column, $post_id) {
        switch ($column) {
            case 'diseases':
                echo get_post_meta($post_id, 'diseases', true);
                break;
            case 'symptoms':
                echo get_post_meta($post_id, 'symptoms', true);
                break;
        }
    }
    
    add_action('manage_questionnaire_posts_custom_column', 'display_questionnaire_column_data', 10, 2);

    function simplify_questionnaire_edit_screen() {
        global $post;
    
        // Check if we are editing a "Questionnaire" post
        if ($post->post_type === 'questionnaire') {
            // Check if the form has been submitted
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_type']) && $_POST['post_type'] === 'questionnaire') {
                // Update Diseases and Symptoms
                $diseases = sanitize_text_field($_POST['diseases']);
                $symptoms = sanitize_text_field($_POST['symptoms']);
    
                // Update the custom fields
                update_post_meta($post->ID, 'diseases', $diseases);
                update_post_meta($post->ID, 'symptoms', $symptoms);
            }
    
            // Get the current values of Diseases and Symptoms
            $current_diseases = get_post_meta($post->ID, 'diseases', true);
            $current_symptoms = get_post_meta($post->ID, 'symptoms', true);
            ?>
            <div class="field">
                <label for="diseases">Diseases</label>
                <input type="text" id="diseases" name="diseases" value="<?php echo esc_attr($current_diseases); ?>" class="large-text">
            </div>
            <div class="field">
                <label for="symptoms">Symptoms</label>
                <input type="text" id="symptoms" name="symptoms" placeholder="add | (varticle bar) seperate symptoms" value="<?php echo esc_attr($current_symptoms); ?>" class="large-text">
            </div>
            <?php
        }
    }
    
    add_action('edit_form_after_title', 'simplify_questionnaire_edit_screen');

    // Hook to save custom fields when the "Update" button is clicked
    add_action('save_post', 'save_questionnaire_custom_fields');

    function save_questionnaire_custom_fields($post_id) {
        // Check if this is a "questionnaire" post type
        if (get_post_type($post_id) === 'questionnaire') {
            // Check if the form has been submitted and update Diseases and Symptoms
            if (isset($_POST['diseases'])) {
                $diseases = sanitize_text_field($_POST['diseases']);
                update_post_meta($post_id, 'diseases', $diseases);
            }

            if (isset($_POST['symptoms'])) {
                $symptoms = sanitize_text_field($_POST['symptoms']);
                update_post_meta($post_id, 'symptoms', $symptoms);
            }
        }
    }

    
    function hide_title_field() {
        global $post;
    
        // Check if we are editing a "Questionnaire" post
        if ($post->post_type === 'questionnaire') {
            echo '<style>#titlediv { display: none; }</style>';
        }
    }
    
    add_action('admin_head-post.php', 'hide_title_field');
    add_action('admin_head-post-new.php', 'hide_title_field');

    function modify_questionnaire_edit_link($actions, $post) {
        // Check if this is a 'questionnaire' post
        if ($post->post_type == 'questionnaire') {
            // Modify the "Edit" action link
            $actions['edit'] = '<a href="' . get_edit_post_link($post->ID) . '">Edit Questionnaire</a>';
        }
        return $actions;
    }
    
    add_filter('post_row_actions', 'modify_questionnaire_edit_link', 10, 2);

    


    

    




    function get_disease_options() {
        $query_args = array(
            'post_type' => 'questionnaire', // Your custom post type
            'posts_per_page' => -1, // Retrieve all posts
            'meta_query' => array(
                array(
                    'key' => 'diseases', // The custom field key for diseases
                    'compare' => 'EXISTS', // Check if the field exists
                ),
            ),
        );
    
        $query = new WP_Query($query_args);
    
        $options = array();
    
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $disease = get_post_meta(get_the_ID(), 'diseases', true);
                if ($disease) {
                    $options[] = $disease;
                }
            }
            wp_reset_postdata();
        }
    
        return $options;
    }
    

    function enqueue_select2() {
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js', array('jquery'), '4.1.0-rc.0', true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css');
    }
    add_action('wp_enqueue_scripts', 'enqueue_select2');





    // Shortcode function to display the chatbox
    function display_chatbox() {
        ob_start();
        include(plugin_dir_path(__FILE__) . 'templates/chatbox-template.php');
        return ob_get_clean();
    }

    add_shortcode('enquiry_chatbox', 'display_chatbox');


    // Shortcode handler for "queue-chat"
    function doc_chat_shortcode_content() {
        // ob_start();
        include(plugin_dir_path(__FILE__) . 'templates/doc-chat.php');
        // return ob_get_clean();
    }
    add_shortcode('doc_chat', 'doc_chat_shortcode_content');

    // Hook the create_queue_chat_page function to run when WordPress is initialized
    add_action('init', 'create_doc_chat_page');

    // Function to create a page and add a shortcode
    function create_doc_chat_page() {
        $post_id = -1; // Initialize with -1 to check if the page exists

        // Check if the "queue-chat" page already exists
        $page = get_page_by_path('doc-chat');

        if ($page) {
            // The page already exists, so update its content
            $post_id = $page->ID;
        } else {
            // Create a new page
            $post = array(
                'post_title'   => 'Doc Chat',
                'post_name'    => 'doc-chat',
                'post_content' => '[doc_chat]', // Shortcode and content
                'post_status'  => 'publish',
                'post_type'    => 'page',
            );
            $post_id = wp_insert_post($post);
        }
        $doc_chat_page_id = $post_id;
        return $post_id;
    }





    // Hook the create_queue_chat_page function to run when WordPress is initialized
    add_action('init', 'create_bot_chat_page');

    // Function to create a page and add a shortcode
    function create_bot_chat_page() {
        $post_id = -1; // Initialize with -1 to check if the page exists

        // Check if the "queue-chat" page already exists
        $page = get_page_by_path('bot-chat');

        if ($page) {
            // The page already exists, so update its content
            $post_id = $page->ID;
        } else {
            // Create a new page
            $post = array(
                'post_title'   => 'Bot Chat',
                'post_name'    => 'bot-chat',
                'post_content' => '[enquiry_chatbox]', // Shortcode and content
                'post_status'  => 'publish',
                'post_type'    => 'page',
            );
            $post_id = wp_insert_post($post);
        }
        $bot_chat_page_id = $post_id;
        return $post_id;
    }






    add_action( 'admin_enqueue_scripts', 'enqueue_ajax_script' );
    function enqueue_ajax_script() {
        wp_enqueue_script('my-ajax-script', admin_url('admin-ajax.php'), array('jquery'), '1.0', true);

        // Pass the URL to your JavaScript file
        wp_localize_script('my-ajax-script', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    add_action('wp_enqueue_scripts', 'enqueue_ajax_script');




    function fetch_specific_data() {
        global $wpdb;
    
        $disease = sanitize_text_field($_POST['disease']); // Sanitize the input
        $args = array(
            'post_type' => 'questionnaire', // Specify the custom post type
            'posts_per_page' => -1, // Retrieve all posts
            'meta_query' => array(
                array(
                    'key' => 'diseases',
                    'value' => $disease,
                    'compare' => '=',
                ),
            ),
        );
    
        $query = new WP_Query($args);
    
        if ($query->have_posts()) {
            $results = array();
    
            while ($query->have_posts()) {
                $query->the_post();
                $symptoms = get_post_meta(get_the_ID(), 'symptoms', true);
                if (!empty($symptoms)) {
                    $results[] = array('symptoms' => $symptoms);
                }
            }
    
            echo json_encode($results);
        } else {
            echo json_encode(array('error' => 'No data found'));
        }
    
        wp_reset_postdata(); // Restore the global post data
        die();
    }
    

    add_action('wp_ajax_fetch_specific_data', 'fetch_specific_data');
    add_action('wp_ajax_nopriv_fetch_specific_data', 'fetch_specific_data');






    function create_custom_post_type() {
        $labels = array(
            'name' => 'Enquiries',
            'singular_name' => 'Enquiry',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Enquiry',
            'edit_item' => 'Edit Enquiry',
            'new_item' => 'New Enquiry',
            'view_item' => 'View Enquiry',
            'search_items' => 'Search Enquiries',
            'not_found' => 'No Enquiries found',
            'not_found_in_trash' => 'No Enquiries found in Trash',
            'parent_item_colon' => '',
            'menu_name' => 'Enquiries'
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'enquiries'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor'),
        );

        register_post_type('enquiry', $args);
    }

    add_action('init', 'create_custom_post_type');

    function custom_enquiry_columns($columns) {
        unset($columns['title']);
        $columns['user_id'] = 'User ID';
        $columns['user_name'] = 'Name';
        $columns['reason'] = 'Reason';
        $columns['symptom_starting_date'] = 'Symptom Start Date';
        $columns['symptoms'] = 'Symptoms';
        $columns['additional_symptoms'] = 'Additional Symptoms';
        $columns['has_answered'] = 'Has Answered';
        return $columns;
    }

    add_filter('manage_enquiry_posts_columns', 'custom_enquiry_columns');


    function display_custom_column_data($column, $post_id) {
        switch ($column) {
            case 'user_id':
                echo get_post_meta($post_id, 'user_id', true);
                break;
            case 'user_name':
                echo get_post_meta($post_id, 'user_name', true);
                break;
            case 'reason':
                echo get_post_meta($post_id, 'reason', true);
                break;
            case 'symptom_starting_date':
                echo get_post_meta($post_id, 'symptom_starting_date', true);
                break;
            case 'symptoms':
                echo get_post_meta($post_id, 'symptoms', true);
                break;
            case 'additional_symptoms':
                echo get_post_meta($post_id, 'additional_symptoms', true);
                break;
            case 'has_answered':
                echo get_post_meta($post_id, 'has_answered', true);
                break;
        }
    }
    add_action('manage_enquiry_posts_custom_column', 'display_custom_column_data', 10, 2);


    function insert_enquiry_post_type() {
        // Ensure the user is logged in to create a post
        if (!is_user_logged_in()) {
            die('You must be logged in to create an enquiry.');
        }

        // Collect the data from the AJAX request
        $reason = sanitize_text_field($_POST['reason']);
        $symptomStartingDate = sanitize_text_field($_POST['symptomStartingDate']);
        $symptoms = sanitize_text_field(implode(', ', $_POST['symptoms'])); // Convert the array to a pipe-separated string
        $additionalSymptoms = sanitize_text_field($_POST['additionalSymptoms']);

        // Create an array with the post data
        $new_post = array(
            'post_title' => '', // You can leave this empty or set it to something meaningful
            'post_content' => '',
            'post_status' => 'publish', // Change the status as needed
            'post_type' => 'enquiry', // Your custom post type
            'post_author' => get_current_user_id(), // Get the current user's ID
        );

        // Insert the post into the database
        $post_id = wp_insert_post($new_post);
        $current_user = wp_get_current_user();

        // Update custom post type columns with the collected data
        update_post_meta($post_id, 'user_id', $current_user->ID);
        update_post_meta($post_id, 'user_name', $current_user->display_name);
        update_post_meta($post_id, 'reason', $reason);
        update_post_meta($post_id, 'symptom_starting_date', $symptomStartingDate);
        update_post_meta($post_id, 'symptoms', $symptoms);
        update_post_meta($post_id, 'additional_symptoms', $additionalSymptoms);
        update_post_meta($post_id, 'has_answered', 0);

        if ($post_id) {
            // Post was successfully created
            echo json_encode(array('message' => 'Enquiry submitted successfully.'));
        } else {
            // An error occurred
            echo json_encode(array('error' => 'Enquiry submission failed.'));
        }

        die(); // Always end with die to prevent extra output
    }

    add_action('wp_ajax_insert_enquiry_post_type', 'insert_enquiry_post_type');
    add_action('wp_ajax_nopriv_insert_enquiry_post_type', 'insert_enquiry_post_type');


    function current_user_has_enquiry() {
        // Get the current user's ID
        $current_user_id = get_current_user_id();
    
        // Define the custom post type (change 'enquiry' to your custom post type slug)
        $post_type = 'enquiry';
    
        // Prepare the query to check if the user has a post in the custom post type
        $query = new WP_Query(array(
            'post_type' => $post_type,
            'author' => $current_user_id,
            'posts_per_page' => 1, // You only need to check if they have at least one post
        ));
    
        // Check if there are posts for the current user
        if ($query->have_posts()) {
            return true; // The user has a post in the custom post type
        } else {
            return false; // The user doesn't have a post in the custom post type
        }
    }
    







    // Register 'message' custom post type
    function create_message_post_type() {
        $labels = array(
            'name' => 'Messages',
            'singular_name' => 'Message',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Message',
            'edit_item' => 'Edit Message',
            'new_item' => 'New Message',
            'view_item' => 'View Message',
            'search_items' => 'Search Messages',
            'not_found' => 'No Messages found',
            'not_found_in_trash' => 'No Messages found in Trash',
            'parent_item_colon' => '',
            'menu_name' => 'Messages'
        );

        $args = array(
            'labels' => $labels,
            'public' => false, // Set to false if you don't want it to be publicly accessible
            'publicly_queryable' => false, // Set to false if you don't want it to be publicly queryable
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'message'),
            'capability_type' => 'post',
            'has_archive' => false, // Set to false if you don't want an archive page
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title'),
        );

        register_post_type('message', $args);
    }

    add_action('init', 'create_message_post_type');

    // Add custom fields to 'message' post type
function add_message_custom_fields() {
    add_post_type_support('message', 'custom-fields');
}

add_action('init', 'add_message_custom_fields');


    // Register post meta fields for 'message' custom post type
    function register_message_post_meta() {
        register_post_meta('message', 'enquiry_id', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
        ));
        
        register_post_meta('message', 'senders_id', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
        ));
        
        register_post_meta('message', 'receiver_id', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
        ));
        
        register_post_meta('message', 'message', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
        ));
    }

    add_action('init', 'register_message_post_meta');

    // Customize admin columns for 'message' post type
    function message_custom_columns($columns) {
        $new_columns = array(
            'cb' => $columns['cb'],
            'enquiry_id' => 'Enquiry ID',
            'senders_id' => 'Sender ID',
            'receiver_id' => 'Receiver ID',
            'message' => 'Message',
            'date' => $columns['date'],
        );

        return $new_columns;
    }

    add_filter('manage_message_posts_columns', 'message_custom_columns');

    // Display post meta values in admin columns for 'message' post type
    function message_custom_column_data($column, $post_id) {
        switch ($column) {
            case 'enquiry_id':
                echo get_post_meta($post_id, 'enquiry_id', true);
                break;
            case 'senders_id':
                echo get_post_meta($post_id, 'senders_id', true);
                break;
            case 'receiver_id':
                echo get_post_meta($post_id, 'receiver_id', true);
                break;
            case 'message':
                echo get_post_meta($post_id, 'message', true);
                break;
        }
    }

    add_action('manage_message_posts_custom_column', 'message_custom_column_data', 10, 2);

    // Add custom fields to the 'message' post type edit screen
    function add_message_custom_fields_to_edit_screen() {
        add_meta_box(
            'message_custom_fields',
            'Message Custom Fields',
            'message',
            'normal',
            'default'
        );
    }

    add_action('add_meta_boxes', 'add_message_custom_fields_to_edit_screen');

    function simplify_message_edit_screen() {
        global $post;
    
        // Check if we are editing a "message" post
        if ($post->post_type === 'message') {
            // Check if the form has been submitted
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_type']) && $_POST['post_type'] === 'message') {
                // Update message-related custom fields
                $enquiry_id = isset($_POST['enquiry_id']) ? sanitize_text_field($_POST['enquiry_id']) : '';
                $senders_id = isset($_POST['senders_id']) ? sanitize_text_field($_POST['senders_id']) : '';
                $receiver_id = isset($_POST['receiver_id']) ? sanitize_text_field($_POST['receiver_id']) : '';
                $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    
                // Update the custom fields
                update_post_meta($post->ID, 'enquiry_id', $enquiry_id);
                update_post_meta($post->ID, 'senders_id', $senders_id);
                update_post_meta($post->ID, 'receiver_id', $receiver_id);
                update_post_meta($post->ID, 'message', $message);
            }
    
            // Get the current values of message-related custom fields
            $current_enquiry_id = get_post_meta($post->ID, 'enquiry_id', true);
            $current_senders_id = get_post_meta($post->ID, 'senders_id', true);
            $current_receiver_id = get_post_meta($post->ID, 'receiver_id', true);
            $current_message = get_post_meta($post->ID, 'message', true);
            ?>
            <div class="field">
                <label for="enquiry_id">Enquiry ID</label>
                <input type="text" id="enquiry_id" name="enquiry_id" value="<?php echo esc_attr($current_enquiry_id); ?>" class="large-text">
            </div>
            <div class="field">
                <label for="senders_id">Sender's ID</label>
                <input type="text" id="senders_id" name="senders_id" value="<?php echo esc_attr($current_senders_id); ?>" class="large-text">
            </div>
            <div class="field">
                <label for="receiver_id">Receiver's ID</label>
                <input type="text" id="receiver_id" name="receiver_id" value="<?php echo esc_attr($current_receiver_id); ?>" class="large-text">
            </div>
            <div class="field">
                <label for="message">Message</label>
                <textarea id="message" name="message" class="large-text"><?php echo esc_textarea($current_message); ?></textarea>
            </div>
            <?php
        }
    }
    
    add_action('edit_form_after_title', 'simplify_message_edit_screen');
    

    // Save the custom fields when the post is updated
    function save_message_custom_fields($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // Check if the keys are set in the $_POST array before accessing them
        $enquiry_id = isset($_POST['enquiry_id']) ? sanitize_text_field($_POST['enquiry_id']) : '';
        $senders_id = isset($_POST['senders_id']) ? sanitize_text_field($_POST['senders_id']) : '';
        $receiver_id = isset($_POST['receiver_id']) ? sanitize_text_field($_POST['receiver_id']) : '';
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';

        // Save the custom fields
        update_post_meta($post_id, 'enquiry_id', $enquiry_id);
        update_post_meta($post_id, 'senders_id', $senders_id);
        update_post_meta($post_id, 'receiver_id', $receiver_id);
        update_post_meta($post_id, 'message', $message);
    }

    add_action('save_post', 'save_message_custom_fields');

    function hide_message_title_field() {
        global $post;
    
        // Check if we are editing a "message" post
        if ($post->post_type === 'message') {
            echo '<style>#titlediv { display: none; }</style>';
        }
    }
    
    add_action('admin_head-post.php', 'hide_message_title_field');
    add_action('admin_head-post-new.php', 'hide_message_title_field');
    
    




    //chatbox functionality
    add_action('wp_ajax_fetch_conversations', 'fetch_conversations_callback');
    add_action('wp_ajax_nopriv_fetch_conversations', 'fetch_conversations_callback');

    function fetch_conversations_callback() {
        // Check if the enquiry ID is provided via the AJAX request
        if (isset($_POST['enquiry_id'])) {
            $enquiry_id = intval($_POST['enquiry_id']); // Sanitize and get the enquiry ID
    
            // Query the message posts related to the given enquiry ID
            $conversations = get_posts(array(
                'post_type' => 'message',
                'meta_key' => 'enquiry_id',
                'meta_value' => $enquiry_id,
                'posts_per_page' => -1, // Retrieve all posts
                'order' => 'ASC', // Sort posts in descending order by date
            ));
    
            $messages = array();
    
            foreach ($conversations as $conversation) {
                // Retrieve post meta values for each conversation
                $sender_id = get_post_meta($conversation->ID, 'senders_id', true);
                $receiver_id = get_post_meta($conversation->ID, 'receiver_id', true);
                $message_content = get_post_meta($conversation->ID, 'message', true);
    
                // Create an array for each message
                $message = array(
                    'senders_id' => $sender_id,
                    'receiver_id' => $receiver_id,
                    'message' => $message_content,
                );
    
                $messages[] = $message;
            }
    
            // Return the messages in JSON format
            wp_send_json($messages);
        } else {
            // Return an error response if no enquiry ID is provided
            wp_send_json_error('Enquiry ID not provided');
        }
    }


    // function enqueue_custom_js() {
    //     wp_enqueue_script('queue_chat', get_template_directory_uri() . 'js/queue_chat.js', array('jquery'), '1.0', true);
    
    //     // Localize the script with the appropriate data.
    //     wp_localize_script('queue_chat', 'customJSData', array(
    //         'ajaxurl' => admin_url('admin-ajax.php'),
    //     ));
    // }
    
    // add_action('wp_enqueue_scripts', 'enqueue_custom_js');
    
    



    // Create a function to send a message
    function send_message() {
        // Check if the request is coming from a valid source
        // check_ajax_referer('my_nonce', 'security');
    
        // Get the enquiry ID and message from the AJAX request
        $enquiry_id = isset($_POST['enquiry_id']) ? intval($_POST['enquiry_id']) : 0;
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    
        if (empty($message) || $enquiry_id === 0) {
            wp_send_json_error('Invalid data received.');
        }
    
        // You can add additional checks here, e.g., to verify the user's permissions.
    
        // Determine the sender and receiver IDs based on user roles, etc.
        $current_user_id = get_current_user_id();
        $receiver_id = '10'; // Replace with the appropriate receiver's user ID
    
        // Create a new message post
        $message_post_id = wp_insert_post(array(
            'post_type' => 'message',
            'post_title' => '',
            'post_content' => $message,
            'post_status' => 'publish',
            'post_author' => $current_user_id,
            'meta_input' => array(
                'enquiry_id' => $enquiry_id,
                // 'senders_id' => $current_user_id,
                // 'receiver_id' => $receiver_id,
            ),
        ));

            // Update the sender's and receiver's IDs for the message
            update_post_meta($message_post_id, 'senders_id', $current_user_id);
            update_post_meta($message_post_id, 'receiver_id', $receiver_id);
    }
    

    // Hook the send_message function to the WordPress AJAX action
    add_action('wp_ajax_send_message', 'send_message');
    add_action('wp_ajax_nopriv_send_message', 'send_message');


    




    // // if the user submitted queued question then show this
    // function display_current_user_enquiry() {
    //     if (is_user_logged_in()) {
    //         $current_user = wp_get_current_user();
    
    //         // Create a custom query to retrieve the user's enquiry based on their ID
    //         $enquiry_query = new WP_Query(array(
    //             'post_type' => 'enquiry',
    //             'author' => $current_user->ID,
    //         ));
    
    //         if ($enquiry_query->have_posts()) {
    //             // Display the user's enquiry in a beautiful format
    //             while ($enquiry_query->have_posts()) {
    //                 $enquiry_query->the_post();
    
    //                 $user_id = get_post_meta(get_the_ID(), 'user_id', true);
    //                 $reason = get_post_meta(get_the_ID(), 'reason', true);
    //                 $symptom_starting_date = get_post_meta(get_the_ID(), 'symptom_starting_date', true);
    //                 $symptoms = get_post_meta(get_the_ID(), 'symptoms', true);
    //                 $additional_symptoms = get_post_meta(get_the_ID(), 'additional_symptoms', true);
    
    //                 // Customize the HTML structure for displaying the enquiry
    //                 echo '<div class="enquiry-summary">';
    //                 echo '<h3>Thank you. You have answered the questionnaire. Please wait. Average wait time is 1h 1min 1sec. The doctor will be with you shortly</h3>';
    //                 echo '<p><strong>User ID:</strong> ' . $user_id . '</p>';
    //                 echo '<p><strong>Reason:</strong> ' . $reason . '</p>';
    //                 echo '<p><strong>Symptom Start Date:</strong> ' . $symptom_starting_date . '</p>';
    //                 echo '<p><strong>Symptoms:</strong> ' . $symptoms . '</p>';
    //                 echo '<p><strong>Additional Symptoms:</strong> ' . $additional_symptoms . '</p>';
    //                 echo '</div>';

    //                 // // ob_start();
    //                 //     include(plugin_dir_path(__FILE__) . 'templates/enquiry-summary.php');
    //                 // // ob_get_clean();
    //             }
    //         } else {
    //             echo 'No enquiry found for the current user.';
    //         }
    
    //         wp_reset_postdata();
    //     }
    // }



?>