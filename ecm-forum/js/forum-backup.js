jQuery(document).ready(function($) {
	
	function isMessagingWindowOpen() {
        return $('#ecmMessagingContainer').is(':visible');
    }

// Event handler for like buttons
$('.ecm-like-btn').click(function() {
    var targetElement = $(this); // Store the clicked button element
    var postId = targetElement.data('postid');
    var commentId = targetElement.data('commentid'); // Add commentId data attribute if available

    // Determine the new action as 'like'
    var newAction = 'like';

    $.ajax({
        url: ecm_ajax_object.ecm_ajax_url,
        type: 'post',
        data: {
            action: 'ecm_handle_like_dislike',
            post_id: postId,
            comment_id: commentId, // Pass comment_id if available
            user_action: newAction, // Use the new action
            nonce: ecm_ajax_object.ecm_ajax_nonce
        },
        success: function(response) {
            if (response.likes !== undefined && response.dislikes !== undefined) {
                // Update the text of the clicked like button
                targetElement.text('Liked');
                
                // Update the like and dislike counts within the same container
                var countsContainer;
                if (postId !== undefined) {
                    countsContainer = $('[data-postid="' + postId + '"]');
                } else if (commentId !== undefined) {
                    countsContainer = $('[data-commentid="' + commentId + '"]');
                }

                countsContainer.find('.ecm-like-count').text(response.likes + ' Likes');
                countsContainer.find('.ecm-dislike-count').text(response.dislikes + ' Dislikes');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX error:', textStatus, errorThrown);
        }
    });
});

// Event handler for dislike buttons (for both posts and comments)
$('.ecm-dislike-btn').click(function() {
    var targetElement = $(this); // Store the clicked button element
    var postId = targetElement.data('postid');
    var commentId = targetElement.data('commentid'); // Add commentId data attribute if available

    // Determine the new action as 'dislike'
    var newAction = 'dislike';

    $.ajax({
        url: ecm_ajax_object.ecm_ajax_url,
        type: 'post',
        data: {
            action: 'ecm_handle_like_dislike',
            post_id: postId,
            comment_id: commentId, // Pass comment_id if available
            user_action: newAction, // Use the new action
            nonce: ecm_ajax_object.ecm_ajax_nonce
        },
        success: function(response) {
            if (response.likes !== undefined && response.dislikes !== undefined) {
                // Update the text of the clicked dislike button
                targetElement.text('Disliked');
                
                // Update the like and dislike counts within the same container
                var countsContainer;
                if (postId !== undefined) {
                    countsContainer = $('[data-postid="' + postId + '"]');
                } else if (commentId !== undefined) {
                    countsContainer = $('[data-commentid="' + commentId + '"]');
                }

                countsContainer.find('.ecm-like-count').text(response.likes + ' Likes');
                countsContainer.find('.ecm-dislike-count').text(response.dislikes + ' Dislikes');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX error:', textStatus, errorThrown);
        }
    });
});



    // Function to retrieve messages for a specific chat
    function ecmRetrieveMessages(chatUserId) {
        $.ajax({
            url: ecm_ajax_object.ecm_ajax_url,
            type: 'post',
            data: {
                action: 'ecm_get_messages',
                chat_user_id: chatUserId,
                nonce: ecm_ajax_object.ecm_ajax_nonce
            },
            success: function(response) {
            if (response.success) {
                var messages = response.data;
                var messagesContainer = $('.ecm-chat-messages');
                messagesContainer.empty();

                $.each(messages, function(index, message) {
				var messageClass = message.sender_id == ecm_ajax_object.current_user_id ? 'ecm-chat-message-self' : 'ecm-chat-message-other';
				var messageSender = messageClass == 'ecm-chat-message-self' ? 'You' : message.display_name;
				var bubbleHtml = '<div class="bubble ' + messageClass + '"><strong>' + messageSender + ': </strong>' + message.message + '</div>';
				messagesContainer.append(bubbleHtml);
			});
				scrollToBottomOfChat();
            }
        },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('Message retrieval error:', textStatus, errorThrown);
            }
        });
    }

    // Event handler for the chat send button
    $('.ecm-chat-send').click(function() {
        var message = $('.ecm-chat-input').val();
        var receiver_id = $('#ecmChatRecipient').val();

        if (message.trim() === '') {
            alert('Please enter a message.');
            return;
        }

        $.ajax({
            url: ecm_ajax_object.ecm_ajax_url,
            type: 'post',
            data: {
                action: 'ecm_send_message',
                message: message,
                receiver_id: receiver_id,
                nonce: ecm_ajax_object.ecm_ajax_nonce
            },
            success: function(response) {
                if (response.success) {
                $('.ecm-chat-messages').append('<div class="ecm-chat-message">' + message + '</div>');
                $('.ecm-chat-input').val('');
            } else {
                // Display error message from the server
                alert(response.data);
            }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('Message send error:', textStatus, errorThrown);
            }
        });
    });

    // Variable to store the message check interval
    var messageCheckInterval;

    // Function to start checking for messages
    function startMessageCheck(chatUserId) {
        if (messageCheckInterval) {
            clearInterval(messageCheckInterval);
        }

        messageCheckInterval = setInterval(function() {
            ecmRetrieveMessages(chatUserId);
        }, 5000); // Adjust interval as needed
    }

    function openChat(chatUserId) {
        $('#ecmMessagingContainer').show();
        ecmRetrieveMessages(chatUserId);
        startMessageCheck(chatUserId);
        localStorage.setItem('ecmChatOpen', 'true');
        localStorage.setItem('ecmChatUserId', chatUserId);
    }

    function closeChat() {
        if (messageCheckInterval) {
            clearInterval(messageCheckInterval);
        }
        $('#ecmMessagingContainer').hide();
        localStorage.setItem('ecmChatOpen', 'false');
    }

    $('#ecmOpenChat').click(function() {
        var chatUserId = $('#ecmChatRecipient').val();
        openChat(chatUserId);
    });

    $('#ecmCloseChat').click(function() {
        closeChat();
    });

    // Restore chat state on page load
    if (localStorage.getItem('ecmChatOpen') === 'true') {
        var chatUserId = localStorage.getItem('ecmChatUserId');
        if (chatUserId) {
            openChat(chatUserId);
            $('#ecmChatRecipient').val(chatUserId); // Set correct value in dropdown
        }
    }

    // Dropdown change event for new message recipient
    $('#ecmChatRecipient').change(function() {
        var selectedUserId = $(this).val();
        openChat(selectedUserId);
    });
	
	// Event handler for "Add Friend" button
    $('.ecm-add-friend').click(function() {
        var receiver_id = $(this).data('author-id');
		var current_user_id = ecm_ajax_object.current_user_id; // Assuming you pass this from PHP
		
		if (receiver_id == current_user_id) {
        alert("You cannot add yourself as a friend.");
        return; // Prevent the rest of the function from executing
    	}

        $.ajax({
            url: ecm_ajax_object.ecm_ajax_url,
            type: 'post',
            data: {
                action: 'ecm_send_friend_request',
                receiver_id: receiver_id,
                nonce: ecm_ajax_object.ecm_ajax_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Friend request sent successfully.');
                } else {
                    alert('Failed to send friend request. You may have already friended this user or user has declined your invite.');
					console.log(receiver_id);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('Error sending friend request:', textStatus, errorThrown);
            }
        });
    });
	
	function populateFriendsDropdown() {
    $.ajax({
        url: ecm_ajax_object.ecm_ajax_url,
        type: 'post',
        data: {
            action: 'ecm_get_friends_list',
            nonce: ecm_ajax_object.ecm_ajax_nonce
        },
                
		
        success: function(response) {
            var $dropdown = $('#ecmChatRecipient');
            $dropdown.empty(); // Clear existing options

            console.log('Friends response:', response); // Debugging log

            if (response.success) {
                console.log('Friends data:', response.data); // Additional debugging log

                if (response.data.length > 0) {
                    $.each(response.data, function(i, friend) {
                        $dropdown.append($('<option></option>').attr('value', friend.ID).text(friend.display_name));
                    });
                } else {
                    // This should run if response.data is an empty array
                    console.log('No friends found, appending message'); // Debugging log
                    $dropdown.append($('<option></option>').attr('value', '').text('Add friends to chat'));
                }
            } else {
                // This should run if response.success is false
                console.log('AJAX success false, appending error message'); // Debugging log
                $dropdown.append($('<option></option>').attr('value', '').text('Add friends to chat'));
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('AJAX error:', textStatus, errorThrown);
            $('#ecmChatRecipient').append($('<option></option>').attr('value', '').text('Error loading friends list'));
        }
    });
}

		populateFriendsDropdown();
	
	function fetchAndDisplayFriendRequests() {
		console.log("Fetching friend requests");
    $.ajax({
        url: ecm_ajax_object.ecm_ajax_url,
        type: 'post',
        data: {
            action: 'ecm_get_pending_friend_requests',
            nonce: ecm_ajax_object.ecm_ajax_nonce
        },
        success: function(response) {
            if (response.success) {
                var requestsContainer = $('.ecm-friend-requests');
                requestsContainer.empty();
				
				// Check if there are pending requests and display a visual sign on ecmOpenChat button
                if (response.data.length > 0) {
                    $('#ecmOpenChat').addClass('has-requests');
                } else {
                    $('#ecmOpenChat').removeClass('has-requests');
                }

                $.each(response.data, function(i, request) {
                    var requestHtml = '<div class="friend-request">' +
                                      '<span>' + request.display_name + '</span><br>has requested to be friends.<br><br> ' +
                                      '<button class="accept-friend" data-request-id="' + request.id + '">Accept</button>' +
                                      '<button class="decline-friend" data-request-id="' + request.id + '">Decline</button>' +
                                      '</div>';
                    requestsContainer.append(requestHtml);
                });
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('Error fetching friend requests:', textStatus, errorThrown);
        }
    });
}
	// Event delegation for accepting friend requests
    $(document).on('click', '.accept-friend', function() {
        var requestId = $(this).data('request-id');
        acceptFriendRequest(requestId);
    });

    // Event delegation for declining friend requests
    $(document).on('click', '.decline-friend', function() {
        var requestId = $(this).data('request-id');
        declineFriendRequest(requestId);
    });

    function acceptFriendRequest(requestId) {
        // AJAX call to server to accept the friend request
        $.ajax({
            url: ecm_ajax_object.ecm_ajax_url,
            type: 'post',
            data: {
                action: 'ecm_accept_friend_request', // Update this to your server-side action
                request_id: requestId,
                nonce: ecm_ajax_object.ecm_ajax_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Friend request accepted successfully.');
					location.reload(); // Refresh the page
                    // Optionally, update the UI to reflect the change
                } else {
                    alert('Failed to accept friend request.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('Error accepting friend request:', textStatus, errorThrown);
            }
        });
    }

    function declineFriendRequest(requestId) {
        // AJAX call to server to decline the friend request
        $.ajax({
            url: ecm_ajax_object.ecm_ajax_url,
            type: 'post',
            data: {
                action: 'ecm_decline_friend_request', // Update this to your server-side action
                request_id: requestId,
                nonce: ecm_ajax_object.ecm_ajax_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Friend request declined successfully.');
					location.reload(); // Refresh the page
                    // Optionally, update the UI to reflect the change
                } else {
                    alert('Failed to decline friend request.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('Error declining friend request:', textStatus, errorThrown);
            }
        });
    }
	
	function checkForFriendRequestUpdates() {
		console.log('polling all friends'); // Debugging log
    $.ajax({
        url: ecm_ajax_object.ecm_ajax_url,
        type: 'post',
        data: {
            action: 'ecm_check_friend_request_status',
            nonce: ecm_ajax_object.ecm_ajax_nonce
        },
        success: function(response) {
            if (response.success) {
                // Update chat dropdown with new friends
                updateChatDropdown(response.data);
                // Optionally, display a notification
                displayNotification('Friend request accepted!');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('Error checking friend requests:', textStatus, errorThrown);
        }
    });
}

// Function to update chat dropdown
function updateChatDropdown(newFriends) {
    var $dropdown = $('#ecmChatRecipient');
    
    // Check if the 'No friends available' option exists and remove it
    var noFriendsOption = $dropdown.find('option[value=""]');
    if (noFriendsOption.length) {
        noFriendsOption.remove();
    }

    // Append new friend options to the dropdown
    $.each(newFriends, function(i, friend) {
        $dropdown.append($('<option></option>').attr('value', friend.ID).text(friend.display_name));
		ecmRetrieveMessages();
    });
}

// Function to display a notification
function displayNotification(message) {
    // Implementation depends on how you want to show notifications
    alert(message); // Simple alert, or use a more sophisticated method
}

// Call this function at regular intervals
setInterval(checkForFriendRequestUpdates, 60000); // 10 seconds
	
	$('.ecm-remove-friend').click(function() {
        var friendId = $(this).data('friend-id');

        $.ajax({
            url: ecm_ajax_object.ecm_ajax_url,
            type: 'post',
            data: {
                action: 'ecm_remove_friend',
                friend_id: friendId,
                nonce: ecm_ajax_object.ecm_ajax_nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Friend removed successfully.');
                    // Optionally, remove the friend from the list without page reload
                    $('button[data-friend-id="' + friendId + '"]').parent().remove();
					location.reload(); // Refresh the page
					
                } else {
                    alert('Failed to remove friend.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log('Error removing friend:', textStatus, errorThrown);
            }
        });
    });

$('.ecm-block-unblock-friend').click(function() {
    var friendId = $(this).data('friend-id');
    var action = $(this).data('action');

    console.log('Friend ID:', friendId); // Debugging
    console.log('Action:', action); // Debugging

    $.ajax({
        url: ecm_ajax_object.ecm_ajax_url,
        type: 'post',
        data: {
            action: 'ecm_block_unblock_friend',
            friend_id: friendId,
            block_unblock_action: action, // Changed to avoid conflict with 'action' in AJAX data
            nonce: ecm_ajax_object.ecm_ajax_nonce
        },
        success: function(response) {
            if (response.success) {
                alert(action + ' friend successful.');
                // Update the button text and data-action attribute
                var newAction = action === 'Block' ? 'Unblock' : 'Block';
                $('button[data-friend-id="' + friendId + '"]').text(newAction + ' Friend').data('action', newAction);
            } else {
                alert('Failed to ' + action.toLowerCase() + ' friend.');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('Error ' + action.toLowerCase() + 'ing friend:', textStatus, errorThrown);
            console.log('Response:', jqXHR.responseText); // Additional debugging information
        }
    });
});

// Since the block/unblock buttons are created dynamically, you may need to use event delegation
$(document).on('click', '.ecm-block-unblock-friend', function() {
    // The above code inside this block
});
	
function scrollToBottomOfChat() {
    var chatContainer = $('.ecm-chat-messages');
    chatContainer.scrollTop(chatContainer.prop("scrollHeight"));
}
	
var tabToggles = document.querySelectorAll('.ecm-tab-item .tab-toggle');
tabToggles.forEach(function (tab) {
    tab.addEventListener('click', function (e) {
        var activeTabContent = document.querySelector(tab.getAttribute('href'));

        if (activeTabContent) {
            e.preventDefault();

            // Remove 'active' class from all tabs
            tabToggles.forEach(function (item) {
                item.classList.remove('active');
            });

            // Hide all tab content areas
            var tabContents = document.querySelectorAll('.ecm-tab-content');
            tabContents.forEach(function (content) {
                content.style.display = 'none';
            });

            // Show the clicked tab's content and set it as active
            activeTabContent.style.display = 'block';
            tab.classList.add('active');
        }
        // If no associated content is found, the link behaves normally
    });
});
	
	$('#save-post-btn').click(function() {
    var post_id = $(this).data('postid');
    var parent_id = $(this).data('parentid'); // Get the parent ID if it's a topic
    

    $.ajax({
        url: ecm_ajax_object.ecm_ajax_url,
        type: 'post',
        data: {
            action: 'ecm_save_post',
            post_id: post_id ? post_id : null,
            parent_id: parent_id ? parent_id : null,
            ecm_nonce: ecm_ajax_object.ecm_ajax_nonce
        },
        success: function(response) {
            console.log('AJAX response:', response);
            alert(response.data.message);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('Error:', textStatus, errorThrown);
            alert('AJAX error: ' + textStatus);
        }
    });
});
	
	$('.remove-saved-item').click(function(e) {
        e.preventDefault();

        var itemId = $(this).data('id');
        var itemType = $(this).data('type');

        $.ajax({
            url: ecm_ajax_object.ecm_ajax_url,
            type: 'post',
            data: {
                action: 'ecm_remove_saved_item',
                item_id: itemId,
                item_type: itemType,
                nonce: ecm_ajax_object.ecm_ajax_nonce
            },
            success: function(response) {
                if(response.success) {
                    alert('Item removed successfully.');
                    location.reload(); // Reload the page to update the list
                } else {
                    alert('Error removing item.');
                }
            },
            error: function() {
                alert('Error in AJAX request.');
            }
        });
    });
	
	$('#create-topic-form').submit(function(e) {
        e.preventDefault();

        var formData = {
            'action': 'ecm_create_new_topic',
            'parent_category': $('input[name="parent_category"]').val(),
            'taxonomy_name': $('input[name="taxonomy_name"]').val(),
            'new_topic_name': $('input[name="new_topic_name"]').val(),
            'ecm_nonce': $('input[name="ecm_nonce"]').val()
        };

        $.ajax({
            url: ecm_ajax_object.ecm_ajax_url, // Make sure this variable is correctly enqueued in WordPress
            type: 'post',
            data: formData,
            success: function(response) {
				if(response.success) {
					var message = response.data && response.data.message ? response.data.message : 'Success';
					alert(message);
					location.reload(); // Reload the page to update the list
				} else {
					var errorMessage = response.data && response.data.message ? response.data.message : 'An error occurred';
					alert(errorMessage);
				}
			},
						error: function(jqXHR, textStatus, errorThrown) {
    console.log('AJAX Error:', textStatus, errorThrown);
    console.log('Response Text:', jqXHR.responseText);
    alert('AJAX error: ' + textStatus + ', ' + errorThrown);
}
        });
    });
	
	
		$('#create-category-form').submit(function(e) {
        e.preventDefault();

        var formData = {
			'action': 'ecm_create_new_category',
			'parent_category': $('select[name="parent_category"]').val(),
			'category_weight': $('input[name="category_weight"]').val(),
			'new_category_name': $('input[name="new_category_name"]').val(),
			'ecm_nonce': $('input[name="ecm_nonce"]').val()
		};

        $.ajax({
            url: ecm_ajax_object.ecm_ajax_url, // Make sure this variable is correctly enqueued in WordPress
            type: 'post',
            data: formData,
            success: function(response) {
				if(response.success) {
					var message = response.data && response.data.message ? response.data.message : 'Success';
					alert(message);
					location.reload(); // Reload the page to update the list
				} else {
					var errorMessage = response.data && response.data.message ? response.data.message : 'An error occurred';
					alert(errorMessage);
				}
			},
						error: function(jqXHR, textStatus, errorThrown) {
    console.log('AJAX Error:', textStatus, errorThrown);
    console.log('Response Text:', jqXHR.responseText);
    alert('AJAX error: ' + textStatus + ', ' + errorThrown);
}
        });
    });


    // Activate the first tab by default
    if (tabToggles.length > 0) {
        tabToggles[0].click();
    }
	
	var shareLinkBtn = document.querySelector(".share-link-btn");

    if (shareLinkBtn) {
        shareLinkBtn.addEventListener("click", function() {
            // Get the post's URL
            var postURL = window.location.href;

            // Create a temporary input element to copy the URL to the clipboard
            var tempInput = document.createElement("input");
            tempInput.setAttribute("value", postURL);
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand("copy");
            document.body.removeChild(tempInput);

            // Provide user feedback (you can customize this part)
            alert("Post link copied to clipboard: " + postURL);
        });
	}

	 $('.reply-button').on('click', function() {
    var commentId = $(this).data('comment-id');
	var commentPostId = $('#commentpostid').data('post-id');
    var commentContainer = $(this).closest('.ecm-forum-comment-container');
    var existingForm = $('#custom-comment-form-' + commentId);
	var actionUrl = $('#commentFormActionUrl').data('action-url');
	var authorName = $(this).closest('.post_buttons').data('author-name'); // Adjust this selector based on your HTML
	var imguri = $('#author-info').data('img-uri');

    if (existingForm.length === 0) {
        var replyFormHtml = '<div class="reply-form-container">' +
            '<form id="custom-comment-form-' + commentId + '" action="' + actionUrl + '" method="post">' + // Assuming commentPostUrl is defined
            '<textarea name="comment" id="comment-textarea-' + commentId + '" rows="4"></textarea>' +
            '<input type="hidden" name="comment_post_ID" value="' + commentPostId + '" />' +
			'<input type="hidden" name="comment_parent" value="' + commentId + '" />' +
			'<input type="hidden" name="nonce" value="' + ecm_ajax_object.ecm_ajax_nonce + '" />' + // Include the nonce field
            '<input type="submit" name="submit" value="Submit Comment" />' +
            // Your other form fields and buttons
            '</form>' +
            '</div>';

        commentContainer.after(replyFormHtml);
		 // Re-initialize TinyMCE for the new textarea
		if (commentId == 0){
            tinymce.init({
                selector: '#comment-textarea-' + commentId,
                width: '100%',
				height: 400,
				plugins: [
					"link", "lists", "emoticons", "blockquote", "fontsize", "image", "code"
				],
				toolbar: [
					"undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify",
					"bullist numlist outdent indent | link emoticons blockquote | fontsize | image | code"
				],
				menubar: false,
				images_upload_url: imguri,
				statusbar: false
            });
		} else {
			
			tinymce.init({
                selector: '#comment-textarea-' + commentId,
                width: '100%',
				height: 400,
				setup: function (editor) {
					editor.on('init', function () {
						this.setContent('@' + authorName ); // Add the author's name
					});
				},
				plugins: [
					"link", "lists", "emoticons", "blockquote", "fontsize", "image", "code"
				],
				toolbar: [
					"undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify",
					"bullist numlist outdent indent | link emoticons blockquote | fontsize | image | code"
				],
				menubar: false,
				images_upload_url: imguri,
				statusbar: false
            });
		};

        // Re-initialize TinyMCE for the new textarea
    } else {
        existingForm.find('textarea').focus();
    }
});
	
	$('.reviewed-checkbox').change(function() {
        var reportId = $(this).data('report-id');
        var isReviewed = this.checked ? 1 : 0;

        // Send an AJAX request to update the report status
        $.ajax({
            type: 'POST',
            url: ecm_ajax_object.ecm_ajax_url,
            data: {
                action: 'update_report_status',
                report_id: reportId,
                is_reviewed: isReviewed,
                security: ecm_ajax_object.ecm_nonce,
            },
            success: function(response) {
                // Handle success response if needed
                console.log(response);
            },
            error: function(error) {
                // Handle error if needed
                console.error(error);
            }
        });
    });
	

    // Event handler for moderating a post
    $('.moderate-post').click(function() {
        var postId = $(this).data('post-id');
        
        // Display a confirmation dialog
        var confirmDelete = confirm('As a moderator you are marking this post for deletion bscause it broke forum guidelines. Are you sure? This action cannot be undone.');

        if (confirmDelete) {
            // If the user confirms, perform the deletion
            $.ajax({
                url: ecm_ajax_object.ecm_ajax_url,
                type: 'post',
                data: {
                    action: 'moderate_post',
                    post_id: postId,
                    nonce: ecm_ajax_object.ecm_ajax_nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Replace the post content with the deleted message
                        $('.post[data-postid="' + postId + '"]').html(response.message);
						location.reload();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('AJAX error:', textStatus, errorThrown);
                }
            });
        }
    });
	
	    // Event handler for deleting a post
    $('.delete-post').click(function() {
        var postId = $(this).data('post-id');
        
        // Display a confirmation dialog
        var confirmDelete = confirm('Are you sure you want to delete this post? This action cannot be undone.');

        if (confirmDelete) {
            // If the user confirms, perform the deletion
            $.ajax({
                url: ecm_ajax_object.ecm_ajax_url,
                type: 'post',
                data: {
                    action: 'delete_post',
                    post_id: postId,
                    nonce: ecm_ajax_object.ecm_ajax_nonce
                },
                success: function(response) {
                    if (response.success) {
                    // Redirect to the forum URL
                    var forumURL = (window.location.origin + '/toy_beast/forum/'); // Replace with your forum URL
                    window.location.href = forumURL;
                }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('AJAX error:', textStatus, errorThrown);
                }
            });
        }
    });
	
	// Event handler for moderating a comment
    $('.moderate-comment').click(function() {
        var commentId = $(this).data('comment-id');
        
        // Display a confirmation dialog
        var confirmDelete = confirm('As a moderator you are marking this comment for deletion bscause it broke forum guidelines. Are you sure? This action cannot be undone.');

        if (confirmDelete) {
            // If the user confirms, perform the deletion
            $.ajax({
                url: ecm_ajax_object.ecm_ajax_url,
                type: 'post',
                data: {
                    action: 'moderate_comment',
                    comment_id: commentId,
                    nonce: ecm_ajax_object.ecm_ajax_nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Replace the comment content with the deleted message

						location.reload();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('AJAX error:', textStatus, errorThrown);
                }
            });
        }
    });

    // Event handler for deleting a comment
    $('.delete-comment').click(function() {
        var commentId = $(this).data('comment-id');
        
        // Display a confirmation dialog
        var confirmDelete = confirm('Are you sure you want to delete this comment? This action cannot be undone.');

        if (confirmDelete) {
            // If the user confirms, perform the deletion
            $.ajax({
                url: ecm_ajax_object.ecm_ajax_url,
                type: 'post',
                data: {
                    action: 'delete_comment',
                    comment_id: commentId,
                    nonce: ecm_ajax_object.ecm_ajax_nonce
                },
                success: function(response) {
                    if (response.success) {
						location.reload();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.log('AJAX error:', textStatus, errorThrown);
                }
            });
        }
    });
	

    fetchAndDisplayFriendRequests();


    // Additional code...
});