<?php
/**
 * The snippet for displaying the messaging interface
 */
if ( is_user_logged_in() ) {
?>

<!-- Messaging Container -->
<div class="ecm-messaging-container" id="ecmMessagingContainer" style="display: none;">
	<div class="ecm-friend-requests"></div>
    <!-- Chat Box -->
    <div class="ecm-chat-box">
        <!-- Recipient Selection -->
        <div class="ecm-chat-recipient">
            <label for="ecmChatRecipient">Send to:</label>
            <select id="ecmChatRecipient">
				<!-- Options will be populated by JavaScript -->
			</select>
        </div>

        <!-- Chat Content -->
        <div class="ecm-chat-container">
            <div class="ecm-chat-messages"></div>
			message:<br>
            <textarea class="ecm-chat-input"></textarea><br>
            <button class="ecm-chat-send">Send</button>
        </div>

        <!-- Close Button -->
        <button id="ecmCloseChat" class="ecm-close-btn">X</button>
    </div>
</div>
<?php } ?>