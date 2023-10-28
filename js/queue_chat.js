jQuery(document).ready(function($) {
    var currentEnquiryId;
    var autoScroll = true; // Flag to enable or disable auto-scroll

    function scrollToBottom() {
        var messageBox = $(".chatbox-messages");
        if (autoScroll) {
            messageBox.scrollTop(messageBox[0].scrollHeight);
        }
    }

    function displayConversation(messages) {
        var $chatboxMessages = $(".chatbox-messages");

        // Clear the current messages in the chatbox
        $chatboxMessages.empty();

        // Iterate through the fetched messages and append them to the chatbox
        for (var i = 0; i < messages.length; i++) {
            var message = messages[i];

            // Determine whether the message is sent or received based on sender_id and receiver_id
            var isReceived = message.senders_id !== '1'; // Adjust '1' as needed

            // Create the message element
            var $message = $("<div>", { class: "message " + (isReceived ? "received" : "sent") });
            var $messageContent = $("<div>", { class: "message-content" }).text(message.message);

            $message.append($messageContent);
            $chatboxMessages.append($message);
        }
        scrollToBottom();
    }

    function update_chat(currentEnquiryId) {
        $.ajax({
            url: myAjax.ajaxurl,
            type: "POST",
            data: {
                action: "fetch_conversations",
                enquiry_id: currentEnquiryId,
            },
            success: function(response) {
                console.log(response);
                displayConversation(response);
            },
            error: function(error) {
                console.log("Error:", error);
            },
        });
    }

    // Add a click event listener to all elements with the class "enquiry-summary"
    $(".enquiry-summary").on("click", function() {
        var enquiryId = $(this).data("enquiry-id");
        currentEnquiryId = enquiryId;
        autoScroll = true; // Re-enable auto-scroll
        console.log(enquiryId);
        update_chat(currentEnquiryId);
    });

    $("#send-button").on("click", function () {
        sendMessage();
    });

    // Handle Enter key press in the message input field
    $("#message-input").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Modify the sendMessage function to send an empty message
    function sendMessage() {
        var messageInput = $("#message-input").val();
        if (messageInput) {
            $.ajax({
                url: myAjax.ajaxurl,
                type: "POST",
                data: {
                    action: "send_message",
                    enquiry_id: currentEnquiryId,
                    message: messageInput
                },
                success: function (response) {
                    $("#message-input").val(""); // Clear the input field
                    autoScroll = true; // Re-enable auto-scroll
                },
                error: function (error) {
                    console.log("Error:", error);
                },
            });
        }
    }

    // Call update_chat every second
    setInterval(function() {
        if (currentEnquiryId) {
            autoScroll = true; // Enable auto-scroll
            update_chat(currentEnquiryId);
        }
    }, 1000);

    // Detect manual scroll and disable auto-scroll temporarily
    $(".chatbox-messages").on("scroll", function() {
        if (this.scrollTop + this.clientHeight < this.scrollHeight) {
            autoScroll = false; // Disable auto-scroll
        } else {
            autoScroll = true; // Enable auto-scroll
        }
    });
});
