<?php
    // If the user doesn't have an enquiry, enqueue styles and scripts
    wp_enqueue_style('chatbox-style', plugins_url('../css/chatbox.css', __FILE__));
    wp_enqueue_script('chatbox-script', plugins_url('../js/chatbox.js', __FILE__), array('jquery'), '1.0', true);
    // The rest of your chatbox HTML and PHP code goes here
    // ...
    $doc_chat_page_url = get_permalink(get_page_by_path('doc-chat'));
    ?>
    <a class="d-none doc-chat-page-url" href="<?php echo $doc_chat_page_url;?>">
    <button class="btn btn-primary">Go to Doctors chat</button>
    </a>
    <?php
?>


    <div class="chatbox">
        <div class="chatbox-header">
            <div class="user-avatar">
                <img src="<?php echo plugin_dir_url(__FILE__) . 'chatbot.png'; ?>" alt="User Avatar">
            </div>
            <div class="user-info">
                <div class="user-name">Prakki Bot</div>
                <!-- <div class="user-status">Online</div> -->
            </div>
        </div>
        <div class="chatbox-messages">
            <!-- <div class="message received">
                <div class="message-content">Hello! How can I help you?</div>
            </div>
            <div class="message sent">
                <div class="message-content">I have a question about your services.</div>
            </div> -->
            <!-- Add more messages as needed -->
            <!-- <div class="message received welcome-message">
                <div class="message-content">Welcome to Prakki Chat! What is the reason for your visit?</div>
            </div> -->






            <!-- Display disease options as buttons -->
            <div class="display-none">
                <select class="reason-select">
                    <option value = ''>Select your reason</option> <!-- Add an empty option to allow deselection -->
                    <?php
                    $disease_options = get_disease_options();
                    foreach ($disease_options as $option) {
                        echo '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
                    }
                    ?>
                </select>
            </div>


        </div>
        <div class="chatbox-input">
            <div id="user-input">
                <input class="normal_text_input" type="text" placeholder="Type your response...">
            </div>

            <button id="send-button">Send</button>
        </div>
    </div>

<?php

?>



