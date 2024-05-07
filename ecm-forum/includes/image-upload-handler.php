<?php
// image-upload-handler.php
// Set proper header for JSON response
header('Content-Type: application/json');
// Include WordPress for functionality
require_once("../../../../wp-load.php");

// Check if the user is logged in and has the capability to upload files
if ( is_user_logged_in() && current_user_can('upload_files') ) {
    
    // Check if there's a file in the upload
    if (!empty($_FILES)) {
        $file = $_FILES['file']['tmp_name'];
        $filename = $_FILES['file']['name'];
        
        // Security check - Validate the file (Ensure it's an image)
        $file_type = wp_check_filetype(basename($filename), null );
        if (in_array($file_type['type'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            
            // WordPress Media Library upload
            $upload_dir = wp_upload_dir(); // Get WordPress upload directory.
            $new_file_path = $upload_dir['path'] . '/' . $filename;
            
            if (move_uploaded_file($file, $new_file_path)) {
                $upload_id = wp_insert_attachment( array(
                    'guid'           => $new_file_path, 
                    'post_mime_type' => $file_type['type'],
                    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                ), $new_file_path );

                // Required for wp_generate_attachment_metadata()
                require_once(ABSPATH . 'wp-admin/includes/image.php');

                // Generate and update attachment metadata
                $attach_data = wp_generate_attachment_metadata( $upload_id, $new_file_path );
                wp_update_attachment_metadata( $upload_id, $attach_data );
				
				// Get the relative path to the uploaded file
				$relative_file_path = str_replace(ABSPATH, '', $new_file_path);

				// Generate the relative URL
				$image_url = site_url() . '/' . $relative_file_path;

                // Return JSON response with the URL of the uploaded image.
                echo json_encode(array(
					'location' => $image_url
				));
            } else {
                // Handle file move error
                echo json_encode(array('error' => 'File upload failed.'));
            }
        } else {
            // Handle invalid file type
            echo json_encode(array('error' => 'Invalid file type.'));
        }
    } else {
        // Handle error: No file uploaded
        echo json_encode(array('error' => 'No file uploaded.'));
    }
} else {
//Handle unauthorized access
echo json_encode(array('error' => 'Unauthorized access.'));
}

exit;
