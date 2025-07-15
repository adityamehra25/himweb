<?php
function custom_contract_form_styles() {
    $template_slug = get_page_template_slug();
    if ($template_slug === 'inc/flow-step-form.php') {
        $version = rand(); // Forces browser to load fresh files

        wp_enqueue_style(
            'contract-form-css',
            get_stylesheet_directory_uri() . '/css/contract-form.css',
            array(),
            $version,
            'all'
        ); 
        // flaptckr
        wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');

        // Enqueue Flatpickr JS
        wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', array('jquery'), null, true);
        wp_enqueue_script(
            'my-customfro-js',
            get_stylesheet_directory_uri() . '/js/custom.js',
            array('jquery'),
            $version,
            true
        );
        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', [], null);
        wp_enqueue_script('jquery'); // Ensure jQuery is loaded
        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', ['jquery'], null, true);
        wp_enqueue_script('sweetalert-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);
        wp_enqueue_script(
            'contract_form',
            get_stylesheet_directory_uri() . '/js/contract_form.js',
            array('jquery'),
            $version,
            true
        );
        wp_localize_script('contract_form', 'ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
      
    }
}
add_action('wp_enqueue_scripts', 'custom_contract_form_styles');

add_image_size('custom_square', 100, 100, true); // Square 200x200 (Hard Crop)
add_image_size('custom_rectangular', 150, 90, true); // Rectangular 300x200 (Hard Crop)
function register_contract_builder_post_type() {
    register_post_type('contract_builder', array(
        'labels' => array(
            'name' => 'Contract Builder',
            'singular_name' => 'Contract Builder'
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'custom-fields'),
        'menu_icon' => 'dashicons-clipboard',
        'show_in_rest' => true,
    ));

    register_taxonomy('contract_product', 'contract_builder', array(
        'labels' => array(
            'name' => 'Contract Products',
            'singular_name' => 'Contract Product'
        ),
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
    ));
}
add_action('init', 'register_contract_builder_post_type');

function add_contract_product_fields($taxonomy) {
    ?>
    <div class="form-field">
        <label for="contract_product_price">Price</label>
        <input type="text" name="contract_product_price" id="contract_product_price" value="">
        <p>Enter the price for this contract product.</p>
    </div>
    <div class="form-field">
        <label for="contract_product_enable_price">
            <input type="checkbox" name="contract_product_enable_price" id="contract_product_enable_price" value="1">
            Enable Price
        </label>
    </div>
    <?php
}
add_action('contract_product_add_form_fields', 'add_contract_product_fields');

function edit_contract_product_fields($term) {
    $price = get_term_meta($term->term_id, 'contract_product_price', true);
    $enabled = get_term_meta($term->term_id, 'contract_product_enable_price', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="contract_product_price">Price</label></th>
        <td>
            <input type="text" name="contract_product_price" id="contract_product_price" value="<?php echo esc_attr($price); ?>">
            <p class="description">Enter the price for this contract product.</p>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="contract_product_enable_price">Enable Price</label></th>
        <td>
            <input type="checkbox" name="contract_product_enable_price" id="contract_product_enable_price" value="1" <?php checked($enabled, 1); ?>>
        </td>
    </tr>
    <?php
}
add_action('contract_product_edit_form_fields', 'edit_contract_product_fields');

function save_contract_product_meta($term_id) {
    update_term_meta($term_id, 'contract_product_price', $_POST['contract_product_price']);
    update_term_meta($term_id, 'contract_product_enable_price', isset($_POST['contract_product_enable_price']) ? 1 : 0);
}
add_action('created_contract_product', 'save_contract_product_meta');
add_action('edited_contract_product', 'save_contract_product_meta');

// Register "Contract Builder" Custom Post Type
function register_contract_lead_cpt() {
    $labels = array(
        'name'               => 'Contract Leads',
        'singular_name'      => 'Contract Lead',
        'menu_name'          => 'Contract Leads',
        'name_admin_bar'     => 'Contract Lead',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Contract Lead',
        'new_item'           => 'New Contract Lead',
        'edit_item'          => 'Edit Contract Lead',
        'view_item'          => 'View Contract Lead',
        'all_items'          => 'All Contract Leads',
        'search_items'       => 'Search Contract Leads',
        'not_found'          => 'No contract leads found.',
        'not_found_in_trash' => 'No contract leads found in Trash.'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'show_in_menu'       => true,
        'supports'           => array('title', 'editor', 'custom-fields'),
        'has_archive'        => true,
        'rewrite'            => array('slug' => 'contract-lead'),
        'show_in_rest'       => true,
    );

    register_post_type('contract_lead', $args);
}
add_action('init', 'register_contract_lead_cpt');


function handle_contract_form_submission() {
    error_log('AJAX function triggered');
    error_log(print_r($_POST, true));

    if (!isset($_POST['action']) || $_POST['action'] !== 'submit_contract_form') {
        wp_send_json_error(['message' => 'Invalid request']);
    }
        // Handle file upload
    $client_logo_url = '';
    if (!empty($_FILES['client_logo']['name'])) {
        $file = $_FILES['client_logo'];
    
        // Upload the file to WordPress
        $upload = wp_handle_upload($file, ['test_form' => false]);
    
        if (!isset($upload['error'])) {
            $file_url = $upload['url']; // Get uploaded file URL
            $file_path = $upload['file']; // Get uploaded file path
    
            // Prepare attachment data
            $filetype = wp_check_filetype($file_path);
            $attachment = [
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_file_name($file['name']),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ];
    
            // Insert attachment into media library
            $attachment_id = wp_insert_attachment($attachment, $file_path);
    
            // Generate attachment metadata and update database
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attach_data);
    
            // Get the image URL in medium size (or another size if needed)
            $client_logo_url = wp_get_attachment_image_url($attachment_id, 'full');
    
        }
    }
  
    $contract_type = sanitize_text_field($_POST['contract_type'] ?? '');
    $person_select = sanitize_text_field($_POST['person_select'] ?? '');
    $extra_terms = sanitize_text_field($_POST['extra_terms'] ?? '');
	$contract_period_num = sanitize_text_field($_POST['contract_period_num'] ?? '');
	
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $contract_period = sanitize_text_field($_POST['contract_period'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $title = sanitize_text_field($_POST['title'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $date = sanitize_text_field($_POST['date'] ?? '');
    $company = sanitize_text_field($_POST['company'] ?? '');
    //address 1
    $a1_street_appartment = sanitize_text_field($_POST['a1_street_appartment'] ?? '');
    $a1_city = sanitize_text_field($_POST['a1_city'] ?? '');
    $a1_state = sanitize_text_field($_POST['a1_state'] ?? '');
    $a1_zipcode = sanitize_text_field($_POST['a1_zipcode'] ?? '');
    $a1_country = sanitize_text_field($_POST['a1_country'] ?? '');
    // Collect Address Parts
    $address_parts = array_filter([
        $a1_street_appartment,
        $a1_city,
        $a1_state,
        $a1_zipcode,
        $a1_country
    ]);

    // Create Address String
    $formatted_address1 = implode(', ', array_map('esc_html', $address_parts));

    //address 2
    $a2_street_appartment = sanitize_text_field($_POST['a2_street_appartment'] ?? '');
    $a2_city = sanitize_text_field($_POST['a2_city'] ?? '');
    $a2_state = sanitize_text_field($_POST['a2_state'] ?? '');
    $a2_zipcode = sanitize_text_field($_POST['a2_zipcode'] ?? '');
    $a2_country = sanitize_text_field($_POST['a2_country'] ?? '');

    // product selected
    $contract_product = $_POST['contract_product'] ?? [];
    $new_price = $_POST['new_price'] ?? [];
    $payment_terms = $_POST['payment_terms'] ?? [];
     //echo "<pre>"; print_r($contract_product);
    // die();
  // Filter out categories that have children or a set priority
  $filtered_contract_product = array_filter($contract_product, function ($category) {
    return (!empty($category['child']) && is_array($category['child'])) || isset($category['priority']);
});

// Sort by priority (lowest first), ensuring empty priorities go last
usort($filtered_contract_product, function ($a, $b) {
    $priorityA = (isset($a['priority']) && is_numeric($a['priority'])) ? (int) $a['priority'] : PHP_INT_MAX;
    $priorityB = (isset($b['priority']) && is_numeric($b['priority'])) ? (int) $b['priority'] : PHP_INT_MAX;

    return $priorityA <=> $priorityB;
});

    
    
    $full_name = trim("$first_name $last_name");
    $current_user_id = get_current_user_id(); // Get logged-in user ID
   // Insert Post
   if($contract_type=="a-la-carte"){
   $post_id = wp_insert_post([
        'post_title'  => 'contract_', // Temporary title, will update after getting post_id
        'post_status' => 'publish',
        'post_type'   => 'contract_lead',
        'post_author' => $current_user_id, // Assign post to current user
    ]);
    if ($post_id) {
        // Update Post Title
        wp_update_post([
            'ID'         => $post_id,
            'post_title' => 'contract_' . $post_id
        ]);
    $contract_product_db = !empty($_POST['contract_product']) ? serialize($_POST['contract_product']) : '';
    $new_price_db = !empty($_POST['new_price']) ? serialize($_POST['new_price']) : '';
    $payment_terms_db = !empty($_POST['payment_terms']) ? serialize($_POST['payment_terms']) : '';
        // Save Custom Fields
        update_post_meta($post_id, 'contract_type', $contract_type);
        update_post_meta($post_id, 'first_name', $first_name);
        update_post_meta($post_id, 'last_name', $last_name);
        update_post_meta($post_id, 'title', $title);
        update_post_meta($post_id, 'email', $email);
        update_post_meta($post_id, 'date', $date);
        update_post_meta($post_id, 'company', $company);
        update_post_meta($post_id, 'contract_period', $contract_period);
        update_post_meta($post_id, 'person_select', $person_select);
        update_post_meta($post_id, 'extra_terms', $extra_terms);
		 
		update_post_meta($post_id, 'contract_period_num', $contract_period_num);

        // Document URL (Optional, you can modify this part)
        update_post_meta($post_id, 'document_url', '');
        update_post_meta($post_id, 'a1_street_appartment', $a1_street_appartment);
        update_post_meta($post_id, 'a1_city', $a1_city);
        update_post_meta($post_id, 'a1_zipcode', $a1_zipcode);
        update_post_meta($post_id, 'a1_state', $a1_state);
        update_post_meta($post_id, 'a1_country', $a1_country);

        update_post_meta($post_id, 'a2_street_appartment', $a2_street_appartment);
        update_post_meta($post_id, 'a2_city', $a2_city);
        update_post_meta($post_id, 'a2_zipcode', $a2_zipcode);
        update_post_meta($post_id, 'a2_state', $a2_state);
        update_post_meta($post_id, 'a2_country', $a2_country);
        // Save Client Logo URL
        if ($client_logo_url) {
            update_post_meta($post_id, 'client_logo', $client_logo_url);
            update_post_meta($post_id, 'attachment_id', $attachment_id);
        }
        // Save Serialized Arrays
        update_post_meta($post_id, 'contract_product', $contract_product_db);
        update_post_meta($post_id, 'new_price', $new_price_db);
        update_post_meta($post_id, 'payment_terms', $payment_terms_db);
        if($payment_terms[0]=='Other'){
            update_post_meta($post_id, 'other_payment_terms', $_POST['other_payment_terms']);
        }else{
            update_post_meta($post_id, 'other_payment_terms', '');
        }
        $msg = '<div class="updated notice is-dismissible"><p>Contract successfully saved!</p></div>';
   
    $html = '<div class="">
        <!-- Client Details -->
        <h2>Client Detail</h2>
        <div class="client-details">
            <div>';
            if (!empty($full_name)) {
                 $html .= '<p><strong>Name:</strong>' . esc_html($full_name) . '</p>';
            }
            if (!empty($title)) {
                $html .= '<p><strong>Title:</strong>' . esc_html($title) . '</p>';
            }
            if (!empty($email)) {
                $html .= '<p><strong>Email:</strong>' . esc_html($email) . '</p>';
            }
             $html .= '</div>
            <div>';
            if (!empty($formatted_address1)) {
                $html .= '<p><strong>Address:</strong>'.$formatted_address1.'</p>';
            }
            if (!empty($company)) {
                $html .= '<p><strong>Company:</strong>'.esc_html($company).'</p>';
            }
            if (!empty($date)) {
                $html .= '<p><strong>Date:</strong>'.esc_html($date).'</p>';
            }
            $html .= '</div>
        </div>';

       
        if(!empty($filtered_contract_product)){
         $html .= ' <!-- Products Section --><div class="products">
            <h2>Products</h2>
            <ul>';
     
    foreach($filtered_contract_product as $cat){
        $html .= '<li>'.get_contract_product($cat['parentid']).'</li>';

        foreach($cat['child'] as $subcatid){
            $html .= ' <p class="expansions">'.get_contract_product($subcatid).'</p>';
        }
    }
    $html .=   '</ul>
        </div>';
        }

        if(!empty($payment_terms)){
         $html .= '
         <!-- Payment Term -->
         <div class="payment-term">
            <h2>Payment Term</h2>
            <p>'.$payment_terms[0].'</p>';
            if($payment_terms[0]=='Other'){
                $html .= '<p>'.$_POST['other_payment_terms'].'</p>';
            }
       $html .= '</div>';
        }  
$all_ids = [];
$filtered_contract_product = [];

        foreach ($contract_product as $key => $product_cat) {
            if (!empty($product_cat['parentid'])) {
                $filtered_contract_product[$key] = $product_cat;
            }
        }
        uasort($filtered_contract_product, function ($a, $b) {
            return ($a['priority'] ?? PHP_INT_MAX) <=> ($b['priority'] ?? PHP_INT_MAX);
        });
        foreach ($filtered_contract_product as $key => $product_cat) {
            // Only proceed if 'parentid' is set and not empty
            if (!empty($product_cat['parentid'])) {
                // If child exists, collect only child IDs
                if (!empty($product_cat['child']) && is_array($product_cat['child'])) {
                    foreach ($product_cat['child'] as $child_id) {
                        $all_ids[] = $child_id;
                    }
                } else {
                    // If no child, collect the parent ID
                    $all_ids[] = $product_cat['parentid'];
                }
            }
        }


	
        $html .= '
        <!-- Custom Price Table -->
        <div class="custom-pricing">
            <h2>Custom Price</h2>
            <table>
                <tbody><tr>
                    <th>Product</th>
                    <th>Default Price</th>
                    <th>New Price</th>
                </tr>';
                   
                $parent_categories = get_terms(array(
                    'taxonomy'   => 'contract_product',
                    'hide_empty' => false,
                    //'parent'     => 0, // Get only parent categories
					'include'    => $all_ids
                ));
                $term_map = [];

                foreach ($parent_categories as $term) {
                    $term_map[$term->term_id] = $term;
                }
        
                $parent_categories = []; // Reset it
        
                foreach ($all_ids as $id) {
                    if (isset($term_map[$id])) {
                        $parent_categories[] = $term_map[$id];
                    }
                }
                //echo "<pre>"; print_r($parent_categories);
                if (!empty($parent_categories) && !is_wp_error($parent_categories)) :
                   foreach ($parent_categories as $category) :
                    $price = get_term_meta($category->term_id, 'contract_product_price', true);
                    $enabled = get_term_meta($category->term_id, 'contract_product_enable_price', true);
                    $new_price_value = $new_price[$category->term_id] ?? '';
                     // Format prices with 2 decimal places
                    $formatted_price = number_format((float) $price, 2, '.', '');
                    $formatted_new_price = $new_price_value !== '' ? number_format((float) $new_price_value, 2, '.', '') : '';
                        if($enabled==1):
                  $html .= '<tr>
                    <td>'.esc_html($category->name).'</td>
                    <td>$'.esc_html($price).'</td>
                    <td>$' . esc_html($formatted_new_price) . '</td>
                </tr>';
                endif; 
               endforeach; 
               else :
                $html .='<p>No parent categories found.</p>';
           endif;      
             $html .= '  </tbody></table>
        </div>

    
        </div>';
    
        wp_send_json_success([
            'message' => $msg,
            'datahtml' => $html,
            'post_id' => $post_id
        ]);
    } else {
        $html = "";
        $msg =  '<div class="error notice is-dismissible"><p>Failed to save contract. Please try again.</p></div>';
        wp_send_json_error([
            'message' => $msg,
            'datahtml' => $html,
            'post_id' => ''
        ]);
    }
    }else{
        $proposal_id = sanitize_text_field($_POST['proposal'] ?? '');
        $serialized_data = get_post_meta($proposal_id, 'cat_data', true);
     $retrieved_data = unserialize($serialized_data);
                 
     // Sort the data by 'level'
     usort($retrieved_data, function ($a, $b) {
         return $a['level'] <=> $b['level'];
     });
     $post_id = wp_insert_post([
        'post_title'  => 'contract_', // Temporary title, will update after getting post_id
        'post_status' => 'publish',
        'post_type'   => 'contract_lead',
        'post_author' => $current_user_id, // Assign post to current user
    ]);
    if ($post_id) {
        // Update Post Title
        wp_update_post([
            'ID'         => $post_id,
            'post_title' => 'contract_' . $post_id
        ]);
        $contract_product_db = !empty($_POST['contract_product']) ? serialize($_POST['contract_product']) : '';
    $new_price_db = !empty($_POST['new_price']) ? serialize($_POST['new_price']) : '';
    $payment_terms_db = !empty($_POST['payment_terms']) ? serialize($_POST['payment_terms']) : '';
        // Save Custom Fields
        update_post_meta($post_id, 'contract_type', $contract_type);
        update_post_meta($post_id, 'first_name', $first_name);
        update_post_meta($post_id, 'last_name', $last_name);
        update_post_meta($post_id, 'title', $title);
        update_post_meta($post_id, 'email', $email);
        update_post_meta($post_id, 'contract_period', $contract_period);
		update_post_meta($post_id, 'contract_period_num', $contract_period_num);
        update_post_meta($post_id, 'date', $date);
        update_post_meta($post_id, 'company', $company);
        update_post_meta($post_id, 'person_select', $person_select);
        update_post_meta($post_id, 'extra_terms', $extra_terms);
        // Document URL (Optional, you can modify this part)
        update_post_meta($post_id, 'document_url', '');
        update_post_meta($post_id, 'a1_street_appartment', $a1_street_appartment);
        update_post_meta($post_id, 'a1_city', $a1_city);
        update_post_meta($post_id, 'a1_zipcode', $a1_zipcode);
        update_post_meta($post_id, 'a1_state', $a1_state);
        update_post_meta($post_id, 'a1_country', $a1_country);

        update_post_meta($post_id, 'a2_street_appartment', $a2_street_appartment);
        update_post_meta($post_id, 'a2_city', $a2_city);
        update_post_meta($post_id, 'a2_zipcode', $a2_zipcode);
        update_post_meta($post_id, 'a2_state', $a2_state);
        update_post_meta($post_id, 'a2_country', $a2_country);
        // Save Client Logo URL
        if ($client_logo_url) {
            update_post_meta($post_id, 'client_logo', $client_logo_url);
            update_post_meta($post_id, 'attachment_id', $attachment_id);
        }
        // Save Serialized Arrays
        update_post_meta($post_id, 'proposal', $proposal_id);
        
        update_post_meta($post_id, 'payment_terms', $payment_terms_db);
        if($payment_terms[0]=='Other'){
            update_post_meta($post_id, 'other_payment_terms', $_POST['other_payment_terms']);
        }else{
            update_post_meta($post_id, 'other_payment_terms', '');
        }
    }
        $html = '<div class="">
        <!-- Client Details -->
        <h2>Client Detail</h2>
        <div class="client-details">
            <div>';
            if (!empty($full_name)) {
                 $html .= '<p><strong>Name:</strong>' . esc_html($full_name) . '</p>';
            }
            if (!empty($title)) {
                $html .= '<p><strong>Title:</strong>' . esc_html($title) . '</p>';
            }
            if (!empty($email)) {
                $html .= '<p><strong>Email:</strong>' . esc_html($email) . '</p>';
            }
             $html .= '</div>
            <div>';
            if (!empty($formatted_address1)) {
                $html .= '<p><strong>Address:</strong>'.$formatted_address1.'</p>';
            }
            if (!empty($company)) {
                $html .= '<p><strong>Company:</strong>'.esc_html($company).'</p>';
            }
            if (!empty($date)) {
                $html .= '<p><strong>Date:</strong>'.esc_html($date).'</p>';
            }
            $html .= '</div>
        </div>';
		 if (!empty($proposal_id)) {
        $html .= ' <!-- Products Section --><div class="products">
        <h2>Products</h2>
        <ul>';
       for ($level = 1; $level <= 3; $level++) {
           $pCatname = "";
        // Collect posts for this level
        $levelPosts = array_filter($retrieved_data, function ($catData) use ($level) {
           return $catData['level'] == $level;
       });
      
       switch ($level) {
           case 1:
               $cssClass = 'funnel-level-one';
               $parent_cat = 108;
              $parent_cat_name = get_category_name_by_term_id(108);
              $pCatname = str_replace("MOSTLY", "", $parent_cat_name);
               break;
           case 2:
               $cssClass = 'funnel-level-two';
               $parent_cat = 109;
               $parent_cat_name = get_category_name_by_term_id(109);
               $pCatname = str_replace("MOSTLY", "", $parent_cat_name);
               break;
           case 3:
               $cssClass = 'funnel-level-three';
               $parent_cat = 110;
               $parent_cat_name = get_category_name_by_term_id(110);
               $pCatname = str_replace("MOSTLY", "", $parent_cat_name);
               break;
       }
       $html .= '<li>'.$pCatname.'<li>';
       $postCount = count($levelPosts); // Get the number of posts in this level
       $currentPost = 0; // Track current post in the loop
       foreach ($levelPosts as $catData) {
           $html .= '<p class="expansions">'.get_category_name_by_term_id($catData['cat_id']).'</p>';
       }
     
       }
       
       $html .=   '</ul>
           </div>';
		 } 
        if(!empty($payment_terms)){
            $html .= '
            <!-- Payment Term -->
            <div class="payment-term">
               <h2>Payment Term</h2>
               <p>'.$payment_terms[0].'</p>';
               if($payment_terms[0]=='Other'){
                   $html .= '<p>'.$_POST['other_payment_terms'].'</p>';
               }
          $html .= '</div>';
           } 
           $msg = '<div class="updated notice is-dismissible"><p>Contract successfully saved!</p></div>';
           wp_send_json_success([
            'message' => $msg,
            'datahtml' => $html,
            'post_id' => $post_id
        ]);
        
    }
    wp_die();
}

add_action('wp_ajax_submit_contract_form', 'handle_contract_form_submission');
add_action('wp_ajax_nopriv_submit_contract_form', 'handle_contract_form_submission');


function get_contract_product($category_id) {
    // Get the term object by ID
    $term = get_term($category_id, 'contract_product');

    // Check if the term exists and is valid
    if (!is_wp_error($term) && $term) {
        return $term->name; // Return the category name
    }

    return ''; // Return empty string if not found
}

add_action('wp_ajax_submit_genrate_contract', 'submit_generate_contract_callback');
add_action('wp_ajax_nopriv_submit_genrate_contract', 'submit_generate_contract_callback'); // Allow non-logged-in users if needed

function submit_generate_contract_callback() {
    // Check if ID is passed
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        wp_send_json_error(['message' => 'Invalid request, missing ID.']);
    }
    $post_id = $_POST['id'];
    $contract_type = get_post_meta($post_id, 'contract_type', true);
	$contract_type_with = get_post_meta($post_id, 'contract_type', true);
    $first_name = get_post_meta($post_id, 'first_name',true);
    $last_name = get_post_meta($post_id, 'last_name', true);
    $title = get_post_meta($post_id, 'title', true);
    $email = get_post_meta($post_id, 'email', true);
    $date = get_post_meta($post_id, 'date', true);
    $company = get_post_meta($post_id, 'company',true);
    // Document URL (Optional, you can modify this part)
    //get_post_meta($post_id, 'document_url', '');
    $a1_street_appartment = get_post_meta($post_id, 'a1_street_appartment', true);
    $a1_city = get_post_meta($post_id, 'a1_city',true);
    $a1_zipcode = get_post_meta($post_id, 'a1_zipcode', true);
    $a1_state = get_post_meta($post_id, 'a1_state', true);
    $a1_country = get_post_meta($post_id, 'a1_country', true);
    $a2_street_appartment = get_post_meta($post_id, 'a2_street_appartment', true);
    $a2_city = get_post_meta($post_id, 'a2_city',true);
    $a2_zipcode = get_post_meta($post_id, 'a2_zipcode', true);
    $a2_state = get_post_meta($post_id, 'a2_state', true);
    $a2_country = get_post_meta($post_id, 'a2_country', true);
	$adres1 = "";
    if (!empty($a1_street_appartment) && !empty($a1_city) && !empty($a1_state) && !empty($a1_country) && !empty($a1_zipcode)) {
        $adres1 = $a1_street_appartment . '<br>' . $a1_city . ', ' . $a1_state . ' ' . $a1_country . ' ' . $a1_zipcode;
    } 
    $adres2 = "";
    if (!empty($a2_street_appartment) && !empty($a2_city) && !empty($a2_state) && !empty($a2_country) && !empty($a2_zipcode)) {
        $adres2 = $a2_street_appartment . '<br>' . $a2_city . ', ' . $a2_state . ' ' . $a2_country . ' ' . $a2_zipcode;
    } 
    $client_logo_url = get_post_meta($post_id, 'client_logo', true);
    
    $attachment_id =  get_post_meta($post_id, 'attachment_id', true);
    $person_select =  get_post_meta($post_id, 'person_select', true);
    $contract_period =  get_post_meta($post_id, 'contract_period', true);
	$contract_period_num =  get_post_meta($post_id, 'contract_period_num', true);
    $extra_terms =  get_post_meta($post_id, 'extra_terms', true);     
	
    $client_logo_url = get_post_meta($post_id, 'client_logo', true);
    $attachment_id = attachment_url_to_postid($client_logo_url);
    
        
    

    $proposal_id = get_post_meta($post_id, 'proposal', true);
    $category_names_string = "";
    $product_list = "";
    $calculation_table = "";
    $grandTotal ="";
    $totalcourter = "";
    $contract_product = get_post_meta($post_id, 'contract_product', true);
    // Check if the data is serialized
    if(!empty($contract_product)){
    if (is_serialized($contract_product)) {
        $contract_product = unserialize($contract_product);
        $parent_ids = array_column($contract_product, 'parentid');
        
    // Remove null values (for entries without 'parentid')
    $parent_ids = array_filter($parent_ids);


    // Convert to comma-separated string
    $category_names_string = get_category_name_list_columns($parent_ids);
	
        //$category_names_string = implode(', ', $contract_product);
    }

}


    
    $new_price = get_post_meta($post_id, 'new_price', true);
    $new_price = unserialize($new_price);
   
  

    if($contract_type_with=='a-la-carte'){
        $all_ids = [];
        $filtered_contract_product = [];

        foreach ($contract_product as $key => $product_cat) {
            if (!empty($product_cat['parentid'])) {
                $filtered_contract_product[$key] = $product_cat;
            }
        }
        uasort($filtered_contract_product, function ($a, $b) {
            return ($a['priority'] ?? PHP_INT_MAX) <=> ($b['priority'] ?? PHP_INT_MAX);
        });
        foreach ($filtered_contract_product as $key => $product_cat) {
            // Only proceed if 'parentid' is set and not empty
            if (!empty($product_cat['parentid'])) {
                // If child exists, collect only child IDs
                if (!empty($product_cat['child']) && is_array($product_cat['child'])) {
                    foreach ($product_cat['child'] as $child_id) {
                        $all_ids[] = $child_id;
                    }
                } else {
                    // If no child, collect the parent ID
                    $all_ids[] = $product_cat['parentid'];
                }
            }
        }
        
        // Remove duplicates
        $all_ids = array_unique($all_ids);
        
        $parent_categories = get_terms(array(
            'taxonomy'   => 'contract_product',
            'hide_empty' => false,
            //'parent'     => 0, // Get only parent categories
			'include' => $all_ids
        ));
        $term_map = [];

        foreach ($parent_categories as $term) {
            $term_map[$term->term_id] = $term;
        }

        $parent_categories = []; // Reset it

        foreach ($all_ids as $id) {
            if (isset($term_map[$id])) {
                $parent_categories[] = $term_map[$id];
            }
        }

    if (!empty($parent_categories) && !is_wp_error($parent_categories)) :
       // $product_list .= '<ul style="font-family: Poppins;font-weight: 400;font-size: 16px;">';
        $grandTotal = 0;
        $calculation_table .= '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;border: 1px solid black;">
    <tr style="background: #000; color: #fff; border: 1px solid black;">
        <th style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px; text-align: left;border: 1px solid black;">Selection</th>
        <th style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px; text-align: left;border: 1px solid black;">FOUR<span style="color: #3FB488;">CORTERS</span> Solution</th>
        <th style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px; text-align: left;border: 1px solid black;">Investment</th>
    </tr>';
  if (!empty($filtered_contract_product)) {
    $count = 1; // Start from 1

foreach ($filtered_contract_product as $cat) {
    $partentcat = get_term($cat['parentid'], 'contract_product');
    $description_p = $partentcat->description;
    $description_p = str_replace('{company}', $company, $description_p);

    if (!empty($description_p) && empty($cat['child'])) {
        $product_list .= '<div class="about-section mb-5">';

        // Optional logo logic (optional)
		if ($count % 2 !== 0) {
		$product_list .= '<div style="text-align:right;"><span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span></div>';
		}

        $product_list .= '<div style="margin: 30px 0;">';
        $product_list .= '<h2 style="text-align:center;font-family: Poppins;font-weight: 400;font-size: 16px;">' . get_contract_product($cat['parentid']) . '</h2>';
        $product_list .= '<p>' . $description_p . '</p>';
        $product_list .= '</div></div>';

        // 🔁 Add page break every 2 items
        if ($count % 2 === 0) {
            $product_list .= '<div style="page-break-after: always;"></div>';
        }

        $count++;
    }

    foreach ($cat['child'] as $subcatid) {
        $childcat = get_term($subcatid, 'contract_product');
        $description_c = $childcat->description;
        $description_c = str_replace('{company}', $company, $description_c);

        $product_list .= '<div class="about-section mb-5">';

        // Optional logo logic (optional)
		if ($count % 2 !== 0) {
		$product_list .= '<div style="text-align:right;"><span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span></div>';
		}

        $product_list .= '<div style="margin: 30px 0;">';
        $product_list .= '<h2 style="text-align:center;font-family: Poppins;font-weight: 400;font-size: 16px;margin-bottom:20px;">' . get_contract_product($subcatid) . '</h2>';
        $product_list .= '<p>' . $description_c . '</p>';
        $product_list .= '</div></div>';

        // 🔁 Add page break every 2 items
        if ($count % 2 === 0) {
            $product_list .= '<div style="page-break-after: always;"></div>';
        }

        $count++;
    }
}

}



    foreach ($parent_categories as $category) :
        $price = get_term_meta($category->term_id, 'contract_product_price', true);
        $enabled = get_term_meta($category->term_id, 'contract_product_enable_price', true);
        $new_price_value = $new_price[$category->term_id] ?? null; // Allow NULL values
        $category_data = get_term($category->term_id, 'contract_product');
        $description = $category_data->description;
        $description = str_replace('{company}', $company, $description);
        // If new price exists and is not empty, use it; otherwise, use the original price
        $price = (!empty($new_price_value) || $new_price_value === '0') ? $new_price_value : $price;
    
        // Ensure price is numeric and properly formatted
        $formatted_price = number_format((float) $price, 2, '.', '');
        
        // Handle case when new price is empty
        $formatted_new_price = (!empty($new_price_value) || $new_price_value === '0') 
            ? number_format((float) $new_price_value, 2, '.', '') 
            : '0.00';
    
        if ($enabled == 1):
            $grandTotal += (float) $price; // Ensure numeric value for addition
           
            $calculation_table .= '<tr style="border: 1px solid black;">
             <td style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px;border: 1px solid black;text-align:center;">X</td>
                <td style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px;border: 1px solid black;">' . esc_html($category->name) . '</td>
                <td style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px;border: 1px solid black;">$' .number_format($formatted_price, 2) . '</td>
            </tr>';
           
        endif;
    endforeach;
    $getcourterPrice = getcourterPrice($grandTotal);
     $totalcourter = $getcourterPrice['extra_courter']; 
   $calculation_table .= '<tr style="border: 1px solid black;">
        <td colspan="2" style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px; background: #e0e0e0;border: 1px solid black;">Total Investment:</td>
        <td style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px;border: 1px solid black;">$'.number_format($grandTotal, 2).'</td>
    </tr></table>';
  // $product_list .= '</li>';
    endif;  
} 

    if(!empty($proposal_id)){
        $category_names = [];
        $serialized_data = get_post_meta($proposal_id, 'cat_data', true);
     $retrieved_data = unserialize($serialized_data);
                 
     // Sort the data by 'level'
     usort($retrieved_data, function ($a, $b) {
         return $a['level'] <=> $b['level'];
     });
for ($level = 1; $level <= 3; $level++) {
           $pCatname = "";
        // Collect posts for this level
        $levelPosts = array_filter($retrieved_data, function ($catData) use ($level) {
           return $catData['level'] == $level;
       });
      
       switch ($level) {
           case 1:
               $cssClass = 'funnel-level-one';
               $parent_cat = 108;
              $parent_cat_name = get_category_name_by_term_id(108);
              $pCatname = str_replace("MOSTLY", "", $parent_cat_name);
               break;
           case 2:
               $cssClass = 'funnel-level-two';
               $parent_cat = 109;
               $parent_cat_name = get_category_name_by_term_id(109);
               $pCatname = str_replace("MOSTLY", "", $parent_cat_name);
               break;
           case 3:
               $cssClass = 'funnel-level-three';
               $parent_cat = 110;
               $parent_cat_name = get_category_name_by_term_id(110);
               $pCatname = str_replace("MOSTLY", "", $parent_cat_name);
               break;
       }
       $product_list .= '<h3 style="font-family: Poppins;font-weight: bold;font-size: 16px;">'.$pCatname.'</h3><ul style="font-family: Poppins;font-weight: 400;font-size: 16px;">';
       $postCount = count($levelPosts); // Get the number of posts in this level
       $currentPost = 0; // Track current post in the loop
       $product_list = '';
	$product_table = '<table><tr>';
	$category_names = [];
	$column_count = 0;

	foreach ($levelPosts as $catData) {
		$category_name = get_category_name_by_term_id($catData['cat_id']);
		$product_list .= '<li><b>' . $category_name . '</b></li>';
		$category_names[] = $category_name;

		// Add category name to table in 2-column format
		$product_table .= '<td style="vertical-align: top; width: 33.33%;"><b>• ' . $category_name . '</b></td>';
		$column_count++;

		if ($column_count % 3 == 0) {
			$product_table .= '</tr><tr>'; // New row after every 2 items
		}
	}

	// Close open row and table
	$product_table .= '</tr></table>';

       $product_list .= '</ul>';
     
       }
       $category_names_string = implode(", ", $category_names);
      $proposl_calculation = fetch_contract_leads_cal($proposal_id);
     // echo "<pre>"; print_r($proposl_calculation);
   
      if(!empty($proposl_calculation)){
       $grandTotal = $proposl_calculation['grandTotal']; 
       $totalcourter = $proposl_calculation['totalcouter']+$proposl_calculation['extra_courter'];
           $calculation_table = '<table style="width: 100%; border-collapse: collapse; margin-top: 20px;border: 1px solid black;">
            <tr style="background: #000; color: #fff; border: 1px solid black;">
                <th style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px; text-align: left;border: 1px solid black;">FOUR<span style="color: #3FB488;">CORTERS</span> Solution</th>
                <th style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px; text-align: left;border: 1px solid black;">Investment</th>
            </tr>
            <tr style="border: 1px solid black;">
                <td style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px;border: 1px solid black;">'.$proposl_calculation['totalcouter'].' CORTERS at $25,000 each</td>
                <td style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px;border: 1px solid black;">$'.$proposl_calculation['grandTotal'].'</td>
            </tr>
            <tr style="border: 1px solid black;">
                <td style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px;border: 1px solid black;">Bonus, '.$proposl_calculation['extra_courter'].' CORTER ($'.$proposl_calculation['extraPrice'].')</td>
                <td style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px;border: 1px solid black;">$0</td>
            </tr>
            <tr style="border: 1px solid black;">
                <td colspan="1" style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px; background: #e0e0e0;border: 1px solid black;">Total Investment:</td>
                <td style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px;border: 1px solid black;">$'.number_format($proposl_calculation['grandTotal'],2).'</td>
            </tr>
            <tr style="">
                <td colspan="1" style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px;background: #e0e0e0;border: 1px solid black;">Total Value:</td>
                <td style="font-family: Poppins;font-weight: 400;font-size: 16px;padding:0 30px 10px;border: 1px solid black;">$'.$proposl_calculation['totalinvestment'].'</td>
            </tr>
        </table>'; 
      }
      
    }
   
    $payment_terms = get_post_meta($post_id, 'payment_terms', true);
    if($payment_terms=='Other'){
        $other_payment_terms = get_post_meta($post_id, 'other_payment_terms', true);
    }
    $full_name = trim("$first_name $last_name");
    $sign = "";
    $admin_name = "";
   if($person_select=='adam'){
    $sign = '<p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">Best Regards,</p>
        <p><img src="https://staging.fourcorters.com/wp-content/uploads/2025/06/adam-sign.png" alt=""></p>
        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">Adam S. Geiger<br>Vice President <br>FOUR<span style="color: #3FB488;">CORTERS</span> INC.</p>';
        $admin_name = '<p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">Adam S. Geiger<br>Vice President <br>FOUR<span style="color: #3FB488;">CORTERS</span> INC.</p>';
    }else{
        $sign = '<p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">Best Regards,</p>
        <p><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/garry.png" alt=""></p>
        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">Gary Robbins<br>CEO<br>FOUR<span style="color: #3FB488;">CORTERS</span> INC.</p>';
        $admin_name = '<p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">Gary Robbins<br>CEO<br>FOUR<span style="color: #3FB488;">CORTERS</span> INC.</p>';
    }   
    
    $contract_type = str_replace('-', ' ', $contract_type);
    $monthYearq = "";
    if(!empty($date)){
    $monthYear = DateTime::createFromFormat('F d, Y', $date);
    $monthYearq = $monthYear->format('F Y');
    }
   
    //$client_logo_url = wp_get_attachment_image_url($attachment_id, 'medium');
    if ($attachment_id) {
        // Get image metadata (width & height)
        $image_metadata = wp_get_attachment_metadata($attachment_id);
        
        if ($image_metadata) {
            $width = $image_metadata['width'];
            $height = $image_metadata['height'];
    
            if ($width == $height) {
                // Image is square → Get custom 200x200 size
                $client_logo_data = wp_get_attachment_image_src($attachment_id, 'custom_square',true);
                $client_logo_url = $client_logo_data ? $client_logo_data[0] : '';
            } else {
                // Image is rectangular → Get custom 300x200 size
                $client_logo_data = wp_get_attachment_image_src($attachment_id, 'custom_rectangular',true);
                $client_logo_url = $client_logo_data ? $client_logo_data[0] : '';
            }
    
        }
    }
    
    if($contract_type_with=='a-la-carte'){
     
        $html = '<html><head><style>
		@import url("https://fonts.googleapis.com/css2?family=Gothic+A1&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");
    body{ font-family: "Poppins", sans-serif;}
    P{font-family:  "Poppins", sans-serif !important;}
    </style></head><body>';
	$html .='<div class="banner-section mb-5">
        <div class="container" style="background-color: #fff;">
        <table width="100%" style="border-collapse: collapse;">
        <tr>
            <!-- Left column (Logo) taking 66.67% width (8/12) -->
            <td style="width: 66.67%; vertical-align: bottom;">
                <img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;">
            </td>
    
            <!-- Right column (Date) taking 33.33% width (4/12) -->
            <td style="width: 33.33%; text-align: right; vertical-align: bottom;">
              
            </td>
        </tr>
    </table>
            <div class="main-content" style="border-right: 71px solid #063f29; margin-top: 135px;height: 100vh;">
                <div class="row">
                    <div class="col-12" style="margin-left: 50px;">
                        <div class="title" style="font-family: Gothic A1, sans-serif;font-size: 60px; line-height: 50px; margin-bottom: 0;font-weight: 900; color:rgba(63, 180, 69, 1);">HUMAN POWERED</div>
                        <div class="subtitle" style="font-size: 60px; line-height: 56.28px;color: rgba(6, 63, 41, 1); font-weight: 900; font-family: Gothic A1, sans-serif;">MARKETING</div>
                    </div>
                </div>
                <div class="divider" style="margin: 0;border: 1px solid #000;"></div>
                <div class="row text-end" style="margin-top: 180px; padding-right: 30px;">
                    <div class="col-12" style="margin-left: 100px;">
                        <div class="" style="text-align:right; font-family: Lato, system-ui !important; font-size: 36px !important; font-weight: 400; line-height: 30px; color: rgba(0, 0, 0, 1);">A CUSTOM AGREEMENT <br>DESIGNED FOR:</div>
                        <div class="company-name" style="text-align: right;color: #000; margin-left: auto; font-family: Lato, system-ui; font-size: 36px; font-weight: 700 !important; line-height: 43.2px; "<img style="width: 1.93cm; height: 1.93cm;" src="'.$client_logo_url.'" alt=""></div>
                    </div>
                </div>
               <br><br><br><br><br> <br><br><br><br><br> 
            </div>
        </div>
    </div>
    <div style="page-break-after: always;"></div>
	<div class="about-section mb-5">
        <div class="container" style="background-color: #fff;">
        <div style="text-align: right;">
            <img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
        </div>
        <div style="margin: 30px 0;">
            <p style="font-family: Poppins;font-weight: 400;font-size: 16px;line-height: 100%;letter-spacing: 0%;">'.$date.'</p>
            <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">
                '.$full_name.'<br>
                '.$title.'<br>
                '.$company.'<br>
                '.$adres1.'
                '.$adres2.'
            </p>
            <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">Dear &nbsp;'.$first_name.',</p>
            <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">
                Thank you for choosing FOUR<span style="color: #3FB488;">CORTERS</span> as your marketing partner. This agreement outlines '.ucfirst($company).'’s participation in the following FOUR<span style="color: #3FB488;">CORTERS</span> Solutions:
            </p>
            <b style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">Choice of Marketing Solutions including, but not limited to: <b>'.$category_names_string.'
            <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">
                Your investment will provide the features, benefits, and conditions of the selected FOUR<span style="color: #3FB488;">CORTERS</span> Solutions outlined in this document. Please sign and return this agreement to me via email or e-sign, and we will get started. FOUR<span style="color: #3FB488;">CORTERS</span> looks   forward to working with you and your team. 

            </p>
        </div>
        '.$sign.'
    </div></div>
	 <div style="page-break-after: always;"></div>
	    <div class="about-section mb-5">
        <div class="container" style="background-color: #fff;">
       
            '.$product_list.'
            
        
    </div></div>
	
	  <div class="about-section mb-5">
        <div class="container" style="background-color: #fff;">
        <div style="text-align: right;">
            <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
        </div>
        <div style="margin: 30px 0;">
            <h2 style="text-align: center; font-family: Poppins;font-weight: bold;font-size: 16px;">Program Confirmation and Terms of Agreement</h2>
        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">'.$company.' agrees to pay the total investment of $'.number_format($grandTotal,2).' upon receipt of confirmation for the aforementioned FOUR<span style="color: #3FB488;">CORTERS</span> solution in order to guarantee the opportunity. Invoice amounts that have not been paid within thirty (30) days after the due date will thereafter, until paid, be subject to a late payment charge at the lesser of 1.5% per month or the maximum rate permitted under applicable law. Terms of agreement continued on next page. Please select your payment method, and sign below.
</p>
        '.$calculation_table.'
        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">
        Account Name (ACH & Wire): FOUR<span style="color: #3FB488;">CORTERS</span>, Inc.<br>Bank Name: <b>Chase Bank</b></p>
       <ul style="font-family: Poppins; font-weight: 400; font-size: 16px; list-style-type: disc; padding-left: 20px;">
        <li> ACH: ABA Routing Number: <b>044000037</b></li>
        <li>ACH: Account Number: <b>606930629</b></li>
        <li>WIRE: ABA Routing: <b>021000021</b></li>
        <li>WIRE: Account Number: <b>606930629</b></li>
        <li>WIRE: Swift Code: <b>CHASUS33</b></li>
    </ul>					
     <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">Account Name (ACH & Wire):</p>
                   <ul style="font-family: Poppins; font-weight: 400; font-size: 16px; list-style-type: disc; padding-left: 20px;">
					  <li> ACH: ABA Routing Number: <b>044000037</b></li>
					  <li>ACH: Account Number: <b>606930629</b></li>
					  <li>WIRE: ABA Routing: <b>021000021</b></li>
					  <li>WIRE: Account Number: <b>606930629</b></li>
					  <li>WIRE: Swift Code: <b>CHASUS33</b></li>
					</ul>
					<p style="font-family: Poppins;font-weight: 400;font-size: 16px;"><span style="border: 1px solid #000;height: 10px;width: 10px;display: inline-block;"></span> Check- Please send payment to: FOUR<span style="color: #3FB488;">CORTERS</span>, Inc., PO Box 1095, Leesburg, VA 20177<br>
					<span style="border: 1px solid #000;height: 10px;width: 10px;display: inline-block;"></span> Credit Card - If selected, we will provide you contact information to process your payment<br>
					<span style="border: 1px solid #000;height: 10px;width: 10px;display: inline-block;"></span> Wire Transfer - If selected, please see information below to wire transfer the investment
					</p>
					
					<table style="margin-top: 20px; width: 600px;">
				  <tr>
					<td colspan="4" style="font-weight: bold;  padding: 5px;">In Agreement</td>
				  </tr>
				  <tr>
					<td style="padding: 4px; width: 15%;"><strong>Name:</strong></td>
					<td style="padding: 4px; width: 35%; border-bottom: 1px solid #000;"></td>
					<td style="padding: 4px; width: 15%;"><strong>Signature:</strong></td>
					<td style="padding: 4px; width: 35%; border-bottom: 1px solid #000;"></td>
				  </tr>
				  <tr>
					<td style="padding: 4px;"><strong>Title:</strong></td>
					<td style="width: 35%;padding: 4px; border-bottom: 1px solid #000;"></td>
					<td style="padding: 4px;"><strong>Date:</strong></td>
					<td style="width: 35%; padding: 4px; border-bottom: 1px solid #000;"></td>
				  </tr>
				  <tr>
					<td style="padding: 4px;"><strong>Company:</strong></td>
					<td style="width: 35%;padding: 4px; border-bottom: 1px solid #000;"></td>
					<td style="padding: 4px;"><strong>PO#:</strong></td>
					<td style="width: 35%;padding: 4px; border-bottom: 1px solid #000;"></td>
				  </tr>
								</table>
   
        </div>
    </div> </div>
	 <div style="page-break-after: always;"></div>
	 <!-- Page 6 -->   
   <div class="about-section mb-5">
        <div class="container" style="background-color: #fff;">
        <div style="text-align:right;">
            <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
        </div>
        <div style="margin: 30px 0;">
        <ul style="font-family: Poppins;font-weight: 400;font-size: 16px; padding: 0;">
                <li>Hereinafter '.ucfirst($company).' shall be referred to as the "Client."</li>
                <li>FOUR<span style="color: #3FB488;">CORTERS</span> papers, videos, and other assets are research-focused documents and not intended to carry a vendor advertisement, promotion, or endorsement. Final deliverables and assets will note that Client commissioned the research by FOUR<span style="color: #3FB488;">CORTERS</span> to obtain objective, third-party validation, unless otherwise agreed upon by both parties.</li>
                <li>To help ensure a project keeps to the original timeline, effort, and scope, any substantial change in the scope of work, after Client has signed off on the outline or draft, that requires a change of more than 20% of what has been submitted and/or significant additional research shall incur a resetting charge of up to 50%.</li>
                <li>Review Cycle Specifications: For each content asset, '.ucfirst($company).' will have one round of consolidated edits per draft deliverable. For a content asset, edits should be provided using Track Changes in an MS Word document; all internal stakeholder comments should be resolved before submission. Shared online documents will not be accepted. If '.ucfirst($company).' requests a call to review the feedback, the feedback must be provided one (1) full business day prior to the call. This allows the third-party analyst time to review and digest the comments. To ensure efficiency and mitigate the risk of re-writing, FOUR<span style="color: #3FB488;">CORTERS</span> requires sign-off from '.ucfirst($company).' before proceeding to the next phase of the project. The review process includes four phases: Outline, First Draft, Final Draft, and Graphically Enhanced Draft. Should '.ucfirst($company).' require additional review cycles, FOUR<span style="color: #3FB488;">CORTERS</span> will charge a 5% fee.</li>

                <li>FOUR<span style="color: #3FB488;">CORTERS</span> and Client retain ownership of the content created on behalf of this program. Client may disseminate project outcomes at their discretion, provided such dissemination does not alter the deliverables’ original context or intent. FOUR<span style="color: #3FB488;">CORTERS</span> reserves the right to publish or share deliverables, subject to maintaining their original context and intent, unless expressly prohibited by Client in writing.</li>
                
        </ul>
        </div>
    </div>  </div>
	 <div style="page-break-after: always;"></div>
	  <div class="about-section mb-5">
        <div class="container" style="background-color: #fff;">
        <div style="text-align:right;">
            <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
        </div>
        <div style="margin: 30px 0;">
        <ul style="font-family: Poppins;font-weight: 400;font-size: 16px;padding: 0;">
				<li>All projects include any fees required for direct labor and expenses such as research and communication costs. Travel and expenses are billed separately and at cost unless otherwise noted,</li>
				<li>Contract is valid for one (1) year, through '.$monthYearq.'.. Client may extend terms of contract for Six (6) months for a fee of 15% of remaining unused balance. Client must notify FOUR<span style="color: #3FB488;">CORTERS</span> in writing within seven (7) days of the original contract expiration date if it wants to extend the contract. Fee for extension will be due immediately. Any unused balance will be forfeited if it is not allocated or extended by the original expiration date.</li>
                <li>FOUR<span style="color: #3FB488;">CORTERS</span> and Client agree to protect personal data collected or processed during the project. They agree to implement and utilize appropriate security measures to safeguard such data from unauthorized access, disclosure, alteration, or destruction.</li>
                <li>Data protection policies must abide by, but not be limited to, applicable laws including the General Data Protection Regulation (GDPR) and the California Consumer Privacy Act (CCPA). Client may propose their own privacy standards for FOUR<span style="color: #3FB488;">CORTERS</span> approval and use in the related project, provided these standards are at least as stringent as the aforementioned privacy regulations and do not conflict with any known laws or regulations.</li>
                <li>Any violations of this Agreement constitute a breach of contract subject to applicable fines and penalties. By signing, Client representative certifies authority to bind the corporation to this Agreement’s terms and acknowledges understanding of its provisions.</li>
                <li>Neither Client nor its affiliated organizations shall actively solicit employment from any FOUR<span style="color: #3FB488;">CORTERS</span> employee involved in this project for 12 months following project completion.</li>
                <li>There is no representation of certainty, express or implied, by FOUR<span style="color: #3FB488;">CORTERS</span> and its affiliated analysts and analyst firms. Client waives any claim to actual, consequential, or punitive damages against FOUR<span style="color: #3FB488;">CORTERS</span> and its affiliated analysts and analyst firms.</li>
                
        </ul>
	
        </div>
    </div> </div>
	 <div style="page-break-after: always;"></div>
	  <div class="about-section mb-5">
        <div class="container" style="background-color: #fff;">
        <div style="text-align:right;">
            <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
        </div>
        <div style="margin: 30px 0;">
	 <li>If Client is acquired by or acquires another company within the term of this agreement, any outstanding balance for this contract shall be accelerated and become due and immediately payable. Any remaining terms of this contract may need to be renegotiated, and the engagement may be temporarily suspended until a new contract is in place.</li>
                <li>This Agreement shall be governed by Virginia law. Any legal action arising from this Agreement shall be prosecuted in Virginia courts.</li>
                <li>The information contained within this Agreement/contract is intended for the said recipient and is the Intellectual Property of FOUR<span style="color: #3FB488;">CORTERS</span>. It may not be copied, distributed, or disseminated at any time without express written consent by FOUR<span style="color: #3FB488;">CORTERS</span>. If any provision of this Agreement should, for any reason, be held violative of any applicable law, and so much of this Agreement be held unenforceable, then the invalidity of such a specific provision in this Agreement shall not be held to invalidate any other provisions in this Agreement. The other provisions shall remain in full force and effect unless removal of this invalid provision destroys the legitimate purposes of this Agreement, in which event this Agreement shall be cancelled.</li>
        </ul>
		<br><br><br><br><br><br>
					<p style="position: absolute; bottom: 0; left: 0;text-align:left;color: #000;font-size: 11px;font-family: Poppins, sans-serif;">This document contains proprietary business and/or legal information. It is intended solely for FOUR<span style="color: rgba(12, 72, 47, 1);margin-left:-3px">CORTERS</span>, Inc and the named parties and must not be disclosed, reproduced, or distributed in whole or in part without explicit written authorization. Unauthorized use may result in legal action.</p>
        </div>
    </div> </div>
	</body>
</html>
	';

    }else{
        if($contract_period=="monthly"){
			
	$tablecorter = "";
	$tablecorter .= '<table style="width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid black;">
		<tr style="background: #001f0d; color: #fff; border: 1px solid black;">
			<th style="font-family: Poppins; font-weight: 400; font-size: 16px; padding:  0 10px;; text-align: left; border: 1px solid black;">Selection</th>
			<th style="font-family: Poppins; font-weight: 400; font-size: 16px; padding:  0 10px;; text-align: left; border: 1px solid black;">Solution</th>
			<th style="font-family: Poppins; font-weight: 400; font-size: 16px; padding:  0 10px;; text-align: left; border: 1px solid black;">Monthly Investment</th>
		</tr>
		<tr>
			<td style="border: 1px solid black; padding: 10px;">' . ($contract_period_num == 4 ? "X" : "") . '</td>
			<td style="border: 1px solid black; padding: 10px;">4 CORTERS Per Month*</td>
			<td style="border: 1px solid black; padding: 10px;">$100,000</td>
		</tr>
		<tr>
			<td style="border: 1px solid black; padding: 10px;">' . ($contract_period_num == 3 ? "X" : "") . '</td>
			<td style="border: 1px solid black; padding: 10px;">3 CORTERS Per Month*</td>
			<td style="border: 1px solid black; padding: 10px;">$75,000</td>
		</tr>
		<tr>
			<td style="border: 1px solid black; padding: 10px;">' . ($contract_period_num == 2 ? "X" : "") . '</td>
			<td style="border: 1px solid black; padding: 10px;">2 CORTERS Per Month*</td>
			<td style="border: 1px solid black; padding: 10px;">$50,000</td>
		</tr>
		<tr>
			<td style="border: 1px solid black; padding: 10px;">' . ($contract_period_num == 1 ? "X" : "") . '</td>
			<td style="border: 1px solid black; padding: 10px;">1 CORTER Per Month*</td>
			<td style="border: 1px solid black; padding: 10px;">$25,000</td>
		</tr>
	</table>
	<i>* Minimum 6 Month Investment</i><br>';



		$pric_corter = 25000*$contract_period_num;


            $html = '<html><head><style>
		@import url("https://fonts.googleapis.com/css2?family=Gothic+A1&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");
    body{ font-family: "Poppins", sans-serif;}.row{display: flex;} 
    P{font-family:  "Poppins", sans-serif !important;}
    </style></head><body>
                
               <div class="banner-section mb-5">
        <div class="container" style="background-color: #fff;">
        <table width="100%" style="border-collapse: collapse;">
        <tr>
            <!-- Left column (Logo) taking 66.67% width (8/12) -->
            <td style="width: 66.67%; vertical-align: bottom;">
                <img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;">
            </td>
    
            <!-- Right column (Date) taking 33.33% width (4/12) -->
            <td style="width: 33.33%; text-align: right; vertical-align: bottom;">
              
            </td>
        </tr>
    </table>
            <div class="main-content" style="border-right: 71px solid #063f29; margin-top: 135px;height: 100vh;">
                <div class="row">
                    <div class="col-12" style="margin-left: 50px;">
                        <div class="title" style="font-family: Gothic A1, sans-serif;font-size: 60px; line-height: 50px; margin-bottom: 0;font-weight: 900; color:rgba(63, 180, 69, 1);">HUMAN POWERED</div>
                        <div class="subtitle" style="font-size: 60px; line-height: 56.28px;color: rgba(6, 63, 41, 1); font-weight: 900; font-family: Gothic A1, sans-serif;">MARKETING</div>
                    </div>
                </div>
                <div class="divider" style="margin: 0;border: 1px solid #000;"></div>
                <div class="row text-end" style="margin-top: 180px; padding-right: 30px;">
                    <div class="col-12" style="margin-left: 100px;">
                        <div class="" style="text-align:right; font-family: Lato, system-ui !important; font-size: 36px !important; font-weight: 400; line-height: 30px; color: rgba(0, 0, 0, 1);">A CUSTOM AGREEMENT <br>DESIGNED FOR:</div>
                        <div class="company-name" style="text-align: right;color: #000; margin-left: auto; font-family: Lato, system-ui; font-size: 36px; font-weight: 700 !important; line-height: 43.2px; "<img style="width: 1.93cm; height: 1.93cm;" src="'.$client_logo_url.'" alt=""></div>
                    </div>
                </div>
               <br><br><br><br><br> <br><br><br><br><br> 
            </div>
        </div>
    </div>
    <div style="page-break-after: always;"></div>
                <!-- Page 2 -->
               <div class="banner-section mb-5">
        <div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 30px 0;">
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;line-height: 100%;letter-spacing: 0%;">'.$date.'</p>
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">
                            '.$full_name.'<br>
                            '.$title.'<br>
                            '.ucfirst($company).'<br>
                           '.$adres1.'<br>
                            '.$adres2.'
                        </p>
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">Dear '.ucfirst($first_name).',</p>
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">
                            Thank you for choosing FOUR<span style="color: #3FB488;">CORTERS</span> as your marketing partner. This agreement outlines '.ucfirst($company).'’s participation in FOUR<span style="color: #3FB488;">CORTERS</span>’ CORTERBANK, through which '.ucfirst($company).' can leverage for any of the following selected services:
                            '.$product_table.'
                        </p>
                       
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">Please sign and return this agreement to me via email or e-sign, and we will get started. The team at FOUR<span style="color: #3FB488;">CORTERS</span> is excited for the opportunity to collaborate with you - we can’t wait to get started!</p>
                    </div>
                    '.$sign.'
                </div></div>
                <div style="page-break-after: always;"></div>
                <!-- Page 3 -->
                <div class="banner-section mb-5">
        <div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div>
                        <h4 style="font-family: Poppins; font-weight: bold; font-size: 16px;text-align:center">The FOUR<span style="color: #3FB488;">CORTERS</span> CORTERBANK</h4> 
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">
                            Exclusive to FOUR<span style="color: #3FB488;">CORTERS</span>, the CORTERBANK gives '.ucfirst($company).' a flexible, forward-looking program designed to maximize your marketing impact across the year. The CORTERBANK provides a secure, optimized budget and a comprehensive selection of tailored deliverables, including virtual, digital, and in-person solutions.
                            
                            The FOUR<span style="color: #3FB488;">CORTERS</span> CORTERBANK benefits include:
                            <ul>
                            <li>Dedicated program management with a single point of contact across all selected solutions</li>
                            <li>Monthly check-ins to ensure goals are on track and strategy stays aligned</li>
                            <li>Flexibility to pivot, reprioritize, and make decisions in real time</li>
                            <li>Access to a curated selection of more than a dozen virtual, digital, and in-person formats
                            </li>
                            <li>Pricing of all solutions locked per original and attached proposal for a period of one year from confirmation (see appendix A attached)</li>
                            </ul>
                        </p>
                        <b>How It Works</b>
                         <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">
                         '.ucfirst($company).' selects one (1), two (2), or three (3) CORTERS per month (1 Corter = $25,000), with a six-month minimum commitment. The program auto-renews monthly, with the option to opt out after the initial six months.
                         </p>
                         <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">
                         One CORTER is available at the start of the program, which means '.ucfirst($company).' can begin a project immediately. Solutions are launched one at a time, using the balance in the CORTERBANK. Any subsequent solution can be launched when there is at least one full CORTER ($25,000) available in the CORTERBANK.</p>
                             
                        
                    </div>
                </div></div>
              <div style="page-break-after: always;"></div>
                <!-- Page 5 -->
                <div class="banner-section mb-5">
                <div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 30px 0;">
             <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">
                        The program manager will maintain and share a CORTERBANK Checkbook to provide full transparency on all allocations and the remaining balance. If a solution is in progress, the program must remain active until the full value of that solution has been paid. Opting out is only possible after the minimum six (6) month time period and once all active work has been fully funded. If Client pauses or ends the program after 6 months, any unused CORTERS will remain available for allocation for an additional 90 days after which they will expire.  </p>      
                         <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">
                         For every 20 CORTERS accumulated (in consecutive months), CLIENT will receive a complimentary CORTER ($25,000 value).  This is added immediately to the CORTERBANK and can be leveraged upon receipt.
                         </p>     </div>
                </div></div>
             <div style="page-break-after: always;"></div>
                <!-- Page 5 -->
                <div class="banner-section mb-5">
                <div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 30px 0;">
                        <h2 style="text-align: center; font-family: Poppins;font-weight: bold;font-size: 16px;">Program Confirmation and Terms of Agreement</h2>
                    <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">'.ucfirst($company).' agrees to pay the initial monthly investment of $'.number_format($pric_corter, 2).', due upon receipt, for the aforementioned FOUR<span style="color: #3FB488;">CORTERS</span> solution in order to guarantee the opportunity. This investment covers the first month of participation in the program. Subsequent monthly payments of $'.number_format($pric_corter, 2).' will be invoiced at the start of each month, beginning in month two, and are due upon receipt of the invoice. Invoice amounts that have not been paid within 30 days after the due date will thereafter, until paid, be subject to a late payment charge at thelesser of 1.5% per month or the maximum rate permitted under applicable law. Terms of agreement continued on next page</p>
                    
                    '.$tablecorter.'
					<p style="font-family: Poppins;font-weight: 400;font-size: 16px;">Account Name (ACH & Wire):</p>
                   <ul style="font-family: Poppins; font-weight: 400; font-size: 16px; list-style-type: disc; padding-left: 20px;">
					  <li> ACH: ABA Routing Number: <b>044000037</b></li>
					  <li>ACH: Account Number: <b>606930629</b></li>
					  <li>WIRE: ABA Routing: <b>021000021</b></li>
					  <li>WIRE: Account Number: <b>606930629</b></li>
					  <li>WIRE: Swift Code: <b>CHASUS33</b></li>
					</ul>
					<p style="font-family: Poppins;font-weight: 400;font-size: 16px;"><span style="border: 1px solid #000;height: 10px;width: 10px;display: inline-block;"></span> Check- Please send payment to: FOUR<span style="color: #3FB488;">CORTERS</span>, Inc., PO Box 1095, Leesburg, VA 20177<br>
					<span style="border: 1px solid #000;height: 10px;width: 10px;display: inline-block;"></span> Credit Card - If selected, we will provide you contact information to process your payment<br>
					<span style="border: 1px solid #000;height: 10px;width: 10px;display: inline-block;"></span> Wire Transfer - If selected, please see information below to wire transfer the investment
					</p>
					
					<table style="margin-top: 20px; width: 600px;">
				  <tr>
					<td colspan="4" style="font-weight: bold;  padding: 5px;">In Agreement</td>
				  </tr>
				  <tr>
					<td style="padding: 4px; width: 15%;"><strong>Name:</strong></td>
					<td style="padding: 4px; width: 35%; border-bottom: 1px solid #000;"></td>
					<td style="padding: 4px; width: 15%;"><strong>Signature:</strong></td>
					<td style="padding: 4px; width: 35%; border-bottom: 1px solid #000;"></td>
				  </tr>
				  <tr>
					<td style="padding: 4px;"><strong>Title:</strong></td>
					<td style="width: 35%;padding: 4px; border-bottom: 1px solid #000;"></td>
					<td style="padding: 4px;"><strong>Date:</strong></td>
					<td style="width: 35%; padding: 4px; border-bottom: 1px solid #000;"></td>
				  </tr>
				  <tr>
					<td style="padding: 4px;"><strong>Company:</strong></td>
					<td style="width: 35%;padding: 4px; border-bottom: 1px solid #000;"></td>
					<td style="padding: 4px;"><strong>PO#:</strong></td>
					<td style="width: 35%;padding: 4px; border-bottom: 1px solid #000;"></td>
				  </tr>
								</table>
					<p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">
                            
                           '.$adres1.'<br>
                            '.$adres2.'<br>
                            '.$full_name.' and '.$email.'
                        </p>
                    </div>
                </div></div>
           <div style="page-break-after: always;"></div>
                <!-- Page 6 -->
                <div class="banner-section mb-5">
<div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 30px 0;">
                    
                    <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">'.ucfirst($company).' will hereafter be referred to as “Client.”</p>
                    <ul style="font-family: Poppins;font-weight: 400;font-size: 16px; padding: 0;">
                            <li>This Agreement operates on a monthly basis, with an initial six-month minimum commitment. It will continue to auto-renew each month unless Client provides written notice of cancellation after the initial term. To cancel, Client must submit written notice to their designated CORTERBACK (i.e. the program manager) or to grobbins@fourcorters.com at least ten (10) business days before the next billing cycle. Client may not cancel the program during an active project unless and until the full number of CORTERS required for that project are paid. If the program is canceled, any unused balance in the CORTERBANK will remain available for 90 days after termination. After that period, the funds/CORTERS will expire.</li>
                            <li>To help ensure each project keep to the original timeline, effort, and scope, any substantial change in the scope of work, after Client has signed off on an outline or draft, which requires a change of more than 20% of what has been submitted, and/or significant additional research, shall incur a resetting charge of up to 50%.</li>
                            <li>FOUR<span style="color: #3FB488;">CORTERS</span> and Client retain ownership of the content created on behalf of this program. Client may disseminate project outcomes at their discretion, provided such dissemination does not alter the deliverables’ original context or intent. FOUR<span style="color: #3FB488;">CORTERS</span> reserves the right to publish or share deliverables, subject to maintaining their original context and intent, unless expressly prohibited by Client in writing.</li>
                            <li>All projects include any fees required for direct labor and expenses such as research and communication costs.  Travel and expenses are included in this fee.</li>
                            <li>FOUR<span style="color: #3FB488;">CORTERS</span> and Client agree to protect personal data collected or processed during the project. They agree to implement and utilize appropriate security measures to safeguard such data from unauthorized access, disclosure, alteration, or destruction.</li>
                            <li>Neither Client nor its affiliated organizations shall actively solicit employment from any FOUR<span style="color: #3FB488;">CORTERS</span> employee involved in this project for 12 months following project completion.</li>
                    </ul>
                    </div>
                </div>
            <div style="page-break-after: always;"></div>
                <!-- Page 7 -->
               <div class="banner-section mb-5"> 
<div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 30px 0;">
                    <ul style="font-family: Poppins;font-weight: 400;font-size: 16px;padding: 0;">
                            <li>Data protection policies must abide by, but not be limited to, applicable laws including the General Data Protection Regulation (GDPR) and the California Consumer Privacy Act (CCPA). Client may propose their own privacy standards for FOUR<span style="color: #3FB488;">CORTERS</span> approval and use in the related project, provided these standards are at least as stringent as the aforementioned privacy regulations and do not conflict with any known laws or regulations.</li>
                            <li>Any violations of this Agreement constitute a breach of contract subject to applicable fines and penalties. By signing, Client representative certifies authority to bind the corporation to this Agreement’s terms and acknowledges understanding of its provisions.</li>
                            <li>There is no representation of certainty, express or implied, by FOUR<span style="color: #3FB488;">CORTERS</span> and its affiliated analysts and analyst firms.  Client waives any claim to actual, consequential, or punitive damages against FOUR<span style="color: #3FB488;">CORTERS</span> and its affiliated analysts and analyst firms.</li>
                            <li>If Client is acquired by or acquires another company within the term of this agreement, any outstanding balance for this contract shall be accelerated and become due and immediately payable.  Any remaining terms of this contract may need to be renegotiated, and the engagement may be temporarily suspended until a new contract is in place.</li>
                            <li>This Agreement shall be governed by Virginia law. Any legal action arising from this Agreement shall be prosecuted in Virginia courts.</li>
                           
                    </ul>
					
					
                    </div>
                </div></div>
				<div style="page-break-after: always;"></div>
                <!-- Page 8 -->
               <div class="banner-section mb-5"> 
<div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 30px 0;">
                    <ul style="font-family: Poppins;font-weight: 400;font-size: 16px;padding: 0;">
                           <li>The information contained within this Agreement/contract is intended for the said recipient and is the Intellectual Property of FOUR<span style="color: #3FB488;">CORTERS</span>. It may not be copied, distributed, or disseminated at any time without express written consent by FOUR<span style="color: #3FB488;">CORTERS</span>. If any provision of this Agreement should, for any reason, be held violative of any applicable law, and so much of this Agreement be held unenforceable, then the invalidity of such a specific provision in this Agreement shall not be held to invalidate any other provisions in this Agreement. The other provisions shall remain in full force and effect unless removal of this invalid provision destroys the legitimate purposes of this Agreement, in which event this Agreement shall be canceled.</li>
                    </ul>
					
					<br><br><br><br><br><br>
					<p style="position: absolute; bottom: 0; left: 0;text-align:left;color: #000;font-size: 11px;font-family: Poppins, sans-serif;">This document contains proprietary business and/or legal information. It is intended solely for FOUR<span style="color: #3FB488;margin-left:-3px;">CORTERS</span>, Inc and the named parties and must not be disclosed, reproduced, or distributed in whole or in part without explicit written authorization. Unauthorized use may result in legal action.</p>
                    </div>
                </div></div>
                
            </body>
            </html>';  
			
        }else{
		// anunally
		$pric_corter = 25000*$contract_period_num;
		$courter_get =  getcourterPrice($pric_corter);
		$courterPrice = $courter_get['extra_courter']*$courter_get['singlecouter'];
		$courterPriceTotal = $pric_corter+$courterPrice;
		
        $tablecorter = "";
		$tablecorter .= '<table style="width: 100%; margin-top: 20px; border: 1px solid black;">
			<tr style="background: #001f0d; color: #fff; border: 1px solid black;">
				<th style="font-family: Poppins; font-weight: 400; font-size: 16px; padding: 0 10px; text-align: left; border: 1px solid black;">Selection</th>
				<th style="font-family: Poppins; font-weight: 400; font-size: 16px; padding:  0 10px;; text-align: left; border: 1px solid black;">Solution</th>
				<th style="font-family: Poppins; font-weight: 400; font-size: 16px; padding:  0 10px;; text-align: left; border: 1px solid black;">Annual Investment* </th>
			</tr>
			<tr>
				<td style="font-family: Poppins;border: 1px solid black; padding: 10px;text-align:center;">X</td>
				<td style="font-family: Poppins;border: 1px solid black; padding: 10px;">'.$contract_period_num.' CORTERS</td>
				<td style="font-family: Poppins;border: 1px solid black; padding: 10px;">$'.number_format($pric_corter, 2).'</td>
			</tr>
			<tr>
				<td colspan="2" style="font-family: Poppins;border: 1px solid black; padding: 10px;">Bonus</td>
				<td style="border: 1px solid black; padding: 10px;">$'.number_format($courterPrice, 2).'</td>
			</tr>
			<tr>
				<td colspan="2" style="font-family: Poppins;border: 1px solid black; padding: 10px;"><b>Total Value</b></td>
				<td style="font-family: Poppins;border: 1px solid black; padding: 10px;">$'.number_format($courterPriceTotal, 2).'</td>
			</tr>
			
		</table>';


            $html = '<html><head><style>
		@import url("https://fonts.googleapis.com/css2?family=Gothic+A1&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

  
    body{ font-family: "Poppins", sans-serif;} 
    P{font-family:  "Poppins", sans-serif !important;}

    </style></head><body>
                
                <div class="banner-section mb-5">
        <div class="container" style="background-color: #fff;">
        <table width="100%" style="border-collapse: collapse;">
        <tr>
            <!-- Left column (Logo) taking 66.67% width (8/12) -->
            <td style="width: 66.67%; vertical-align: bottom;">
                <img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;">
            </td>
    
            <!-- Right column (Date) taking 33.33% width (4/12) -->
            <td style="width: 33.33%; text-align: right; vertical-align: bottom;">
              
            </td>
        </tr>
    </table>
            <div class="main-content" style="border-right: 71px solid #063f29; margin-top: 135px;height: 100vh;">
                <div class="row">
                    <div class="col-12" style="margin-left: 50px;">
                        <div class="title" style="font-family: Gothic A1, sans-serif;font-size: 60px; line-height: 50px; margin-bottom: 0;font-weight: 900; color:rgba(63, 180, 69, 1);">HUMAN POWERED</div>
                        <div class="subtitle" style="font-size: 60px; line-height: 56.28px;color: rgba(6, 63, 41, 1); font-weight: 900; font-family: Gothic A1, sans-serif;">MARKETING</div>
                    </div>
                </div>
                <div class="divider" style="margin: 0;border: 1px solid #000;"></div>
                <div class="row text-end" style="margin-top: 180px; padding-right: 30px;">
                    <div class="col-12" style="margin-left: 100px;">
                        <div class="" style="text-align:right; font-family: Lato, system-ui !important; font-size: 36px !important; font-weight: 400; line-height: 30px; color: rgba(0, 0, 0, 1);">A CUSTOM AGREEMENT <br>DESIGNED FOR:</div>
                        <div class="company-name" style="text-align: right;color: #000; margin-left: auto; font-family: Lato, system-ui; font-size: 36px; font-weight: 700 !important; line-height: 43.2px; "<img style="width: 1.93cm; height: 1.93cm;" src="'.$client_logo_url.'" alt=""></div>
                    </div>
                </div>
               <br><br><br><br><br> <br><br><br><br><br> 
            </div>
        </div>
    </div>
    <div style="page-break-after: always;"></div>
                <!-- Page 2 -->
                <div class="banner-section mb-5">
<div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 30px 0;">
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;line-height: 100%;letter-spacing: 0%;">'.$date.'</p>
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">
                            '.$full_name.'<br>
                            '.$title.'<br>
                            '.ucfirst($company).'<br>
                           '.$adres1.'
                            '.$adres2.'
                        </p>
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">Dear '.ucfirst($first_name).',</p>
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">
                            Thank you for choosing FOUR<span style="color: #3FB488;">CORTERS</span> as your marketing partner. This agreement outlines '.ucfirst($company).'’s participation in FOUR<span style="color: #3FB488;">CORTERS</span>’ CORTERBANK, through which '.ucfirst($company).' can leverage for any of the following selected services:
                            '.$product_table.'
                        </p>
                       
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">
                            Please sign and return this agreement to me via email or e-sign, and we will get started. The team at FOUR<span style="color: #3FB488;">CORTERS</span> is excited for the opportunity to collaborate with you - we can’t wait to get started!
                        </p>
                    </div>
                    '.$sign.'
                </div></div>
                 <div style="page-break-after: always;"></div>
                <!-- Page 3 -->
                <div class="banner-section mb-5">
<div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 30px 0;">
                        <h4 style="font-family: Poppins; font-weight: bold; font-size: 16px;text-align:center">The FOUR<span style="color: #3FB488;">CORTERS</span> CORTERBANK</h4> 
                        <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">
                            Exclusive to FOUR<span style="color: #3FB488;">CORTERS</span>, the CORTERBANK gives '.ucfirst($company).' a flexible, forward-looking program designed to maximize your marketing impact across the year. The CORTERBANK provides a secure, optimized budget and a comprehensive selection of tailored deliverables, including virtual, digital, and in-person solutions.
                            
                            The FOUR<span style="color: #3FB488;">CORTERS</span> CORTERBANK benefits include:
                            <ul>
                            <li>Dedicated program management with a single point of contact across all 
                            selected solutions</li>
                            <li>Monthly check-ins to ensure goals are on track and strategy stays aligned
                            </li>
                            <li>Flexibility to pivot, reprioritize, and make decisions in real time
                            </li>
                            <li>Access to a curated selection of more than a dozen virtual, digital, and in-
                            person formats
                            </li>
                            <li>Pricing of all solutions locked per original and attached proposal for a period 
                            of one year from confirmation (see appendix A attached)</li>
                            </ul>
                        </p>
                        <b>How It Works</b>
                         <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">
                         '.ucfirst($company).' selects the number of Corters for the year. Every Corter is valued at $25,000 (USD). The terms of the agreement are one (1) year from the signing of this agreement. The designated CORTERBACK (program manager) will maintain and share a CORTERBANK Checkbook to provide full transparency on all allocations and the remaining balance. Service rates are frozen as quoted for the one-year term noted in the agreement, ensuring budget protection.
                         </p>
                         <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">
                         '.ucfirst($company).' can begin a project upon invoicing by FOUR<span style="color: #3FB488;">CORTERS</span>. Invoicing must always precede the selection of services, meaning the value of the invoice must be equal to or greater than the value of the selected solution before the selected program kicks off CORTERS may be applied up until the final day of the agreement.</p> 
						 
                    </div>
                </div></div>
              
            
             <div style="page-break-after: always;"></div>
			 <div class="banner-section mb-5">
<div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 30px 0;">
			 
			 					 <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">
					 The execution of projects funded with CORTERS may extend past the final day of the agreement, as long as they are completed within six (6) months of expiration. To leverage your investment in full, all funds must be allocated before the agreement concludes.</p>      
					 <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">
					 Upon completion of all the program selections, there may be a remaining balance under $25,000 pending that all CORTERS are not used in full. The balance can be applied immediately for more leads if the demand generator is selected, or as a credit to future programs. Any and all CORTERS remaining at the end of the term agreement (12 months) that are not applied may carry over into a new agreement if CORTERBANK is renewed at an equal or greater amount than this agreement.
					 </p>        
					 <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">For every 20 CORTERS purchased, CLIENT will receive a complimentary CORTER ($25,000 value).  This is added immediately to the CORTERBANK and can be leveraged upon receipt.</p>

			       </div>
                </div></div>
           <div style="page-break-after: always;"></div>
<div class="banner-section mb-5">
<div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 30px 0;">
                        <h2 style="text-align: center; font-family: Poppins;font-weight: bold;font-size: 16px;">Program Confirmation and Terms of Agreement</h2>
                    <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">'.ucfirst($company).' agrees to pay the yearly investment of $'.number_format($pric_corter, 2).', due upon receipt, for the aforementioned FOUR<span style="color: #3FB488;">CORTERS</span> solution in order to guarantee the opportunity. This investment covers the first month of participation in the program. Invoice amounts that have not been paid within 30 days after the due date will thereafter, until paid, be subject to a late payment charge at the lesser of 1.5% per month or the maximum rate permitted under applicable law.
                    <i>Terms of agreement continued on next page.</i></p>
                    
                    '.$tablecorter.'
                   
                    
                    <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">Account Name (ACH & Wire):</p>
					 <ul style="font-family: Poppins; font-weight: 400; font-size: 16px; list-style-type: disc; padding-left: 20px;">
					  <li> ACH: ABA Routing Number: <b>044000037</b></li>
					  <li>ACH: Account Number: <b>606930629</b></li>
					  <li>WIRE: ABA Routing: <b>021000021</b></li>
					  <li>WIRE: Account Number: <b>606930629</b></li>
					  <li>WIRE: Swift Code: <b>CHASUS33</b></li>
					</ul>					
					
					<p style="font-family: Poppins;font-weight: 400;font-size: 16px;"><span style="border: 1px solid #000;height: 10px;width: 10px;display: inline-block;"></span> Check- Please send payment to: FOUR<span style="color: #3FB488;">CORTERS</span>, Inc., PO Box 1095, Leesburg, VA 20177<br>
					<span style="border: 1px solid #000;height: 10px;width: 10px;display: inline-block;"></span> Credit Card - If selected, we will provide you contact information to process your payment<br>
					<span style="border: 1px solid #000;height: 10px;width: 10px;display: inline-block;"></span> Wire Transfer - If selected, please see information below to wire transfer the investment
					</p>
					
					<table style="margin-top: 20px; width: 600px;">
				  <tr>
					<td colspan="4" style="font-weight: bold;  padding: 5px;">In Agreement</td>
				  </tr>
				  <tr>
					<td style="padding: 4px; width: 15%;"><strong>Name:</strong></td>
					<td style="padding: 4px; width: 35%; border-bottom: 1px solid #000;"></td>
					<td style="padding: 4px; width: 15%;"><strong>Signature:</strong></td>
					<td style="padding: 4px; width: 35%; border-bottom: 1px solid #000;"></td>
				  </tr>
				  <tr>
					<td style="padding: 4px;"><strong>Title:</strong></td>
					<td style="width: 35%;padding: 4px; border-bottom: 1px solid #000;"></td>
					<td style="padding: 4px;"><strong>Date:</strong></td>
					<td style="width: 35%; padding: 4px; border-bottom: 1px solid #000;"></td>
				  </tr>
				  <tr>
					<td style="padding: 4px;"><strong>Company:</strong></td>
					<td style="width: 35%;padding: 4px; border-bottom: 1px solid #000;"></td>
					<td style="padding: 4px;"><strong>PO#:</strong></td>
					<td style="width: 35%;padding: 4px; border-bottom: 1px solid #000;"></td>
				  </tr>
								</table>
					<p style="font-family: Poppins;font-weight: 400;font-size: 16px;letter-spacing: 0%;">
                            
                           '.$adres1.'
                            '.$adres2.'<br>
                            '.$full_name.' and '.$email.'
                        </p>
					
                    </div>
                </div></div>
           <div style="page-break-after: always;"></div>
<div class="banner-section mb-5">
<div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 30px 0;">
                     
                    <p style="font-family: Poppins;font-weight: 400;font-size: 16px;">'.ucfirst($company).' will hereafter be referred to as “Client.”</p>
                    <ul style="font-family: Poppins;font-weight: 400;font-size: 16px; padding: 0;">
                            <li>Contract is valid for one year, through Month/Year.</li>
                            <li>To help ensure each project keep to the original timeline, effort, and scope, any substantial change in the scope of work, after Client has signed off on an outline or draft, which requires a change of more than 20% of what has been submitted, and/or significant additional research, shall incur a resetting charge of up to 50%.</li>
                            <li>FOUR<span style="color: #3FB488;">CORTERS</span> and Client retain ownership of the content created on behalf of this program. Client may disseminate project outcomes at their discretion, provided such dissemination does not alter the deliverables’ original context or intent. FOUR<span style="color: #3FB488;">CORTERS</span> reserves the right to publish or share deliverables, subject to maintaining their orig original context or intent. FOUR<span style="color: #3FB488;">CORTERS</span> reserves the right to publish or share deliverables, subject to maintaining their original context and intent, unless expressly prohibited by Client in writing.</li>
							<li>All projects include any fees required for direct labor and expenses such as research and communication costs.  Travel and expenses are included in this fee.</li>
							<li>FOUR<span style="color: #3FB488;">CORTERS</span> and Client agree to protect personal data collected or processed during the project. They agree to implement and utilize appropriate security measures to safeguard such data from unauthorized access, disclosure, alteration, or destruction.</li>
							<li>Neither Client nor its affiliated organizations shall actively solicit employment from any FOUR<span style="color: #3FB488;">CORTERS</span> employee involved in this project for 12 months following project completion.</li>
							<li>Data protection policies must abide by, but not be limited to, applicable laws including the General Data Protection Regulation (GDPR) and the California Consumer Privacy Act (CCPA). Client may propose their own privacy standards for FOUR<span style="color: #3FB488;">CORTERS</span> approval and use in the related project, provided these standards are at least as stringent as the aforementioned privacy regulations and do not conflict with any known laws or regulations.</li>
							
							 </ul>
                    </div>
                </div></div>
           <div style="page-break-after: always;"></div>
			<div class="banner-section mb-5">
			<div class="container" style="background-color: #fff;">
                    <div style="text-align:right;">
                        <span style="padding: 5px;"><img src="https://staging.fourcorters.com/wp-content/uploads/2025/03/new-logo.png" alt="Logo" style="width: 150px;"></span>
                    </div>
                    <div style="margin: 20px 0;">
					<li>Any violations of this Agreement constitute a breach of contract subject to applicable fines and penalties. By signing, Client representative certifies authority to bind the corporation to this Agreement’s terms and acknowledges understanding ofits provisions.</li>
					<li>There is no representation of certainty, express or implied, by FOUR<span style="color: #3FB488;">CORTERS</span> and its affiliated analysts and analyst firms.  Client waives any claim to actual, consequential, or punitive damages against FOUR<span style="color: #3FB488;">CORTERS</span> and its affiliated analysts and analyst firms.</li>
                    <ul style="font-family: Poppins;font-weight: 400;font-size: 16px;padding: 0;">
					<li>If Client is acquired by or acquires another company within the term of this agreement, any outstanding balance for this contract shall be accelerated and become due and immediately payable.  Any remaining terms of this contract may need to be renegotiated, and the engagement may be temporarily suspended until a new contract is in place.</li>
					<li>This Agreement shall be governed by Virginia law. Any legal action arising from this Agreement shall be prosecuted in Virginia courts.</li>
					<li>The information contained within this Agreement/contract is intended for the said recipient and is the Intellectual Property of FOUR<span style="color: #3FB488;">CORTERS</span>. It may not be copied, distributed, or disseminated at any time without express written consent by FOUR<span style="color: #3FB488;">CORTERS</span>. If any provision of this Agreement should, for any reason, be held violative of any applicable law, and so much of this Agreement be held unenforceable, then the invalidity of such a specific provision in this Agreement shall not be held to invalidate any other provisions in this Agreement. The other provisions shall remain in full force and effect unless removal of this invalid provision destroys the legitimate purposes of this Agreement, in which event this Agreement shall be canceled.</li>
                    </ul>
					
					<br><br><br><br><br><br>
					<p style="position: absolute; bottom: 0; left: 0;text-align:left;color: #000;font-size: 11px;font-family: Poppins, sans-serif;">This document contains proprietary business and/or legal information. It is intended solely for FOUR<span style="color:#3FB488;margin-left:-3px;">CORTERS</span>, Inc and the named parties and must not be disclosed, reproduced, or distributed in whole or in part without explicit written authorization. Unauthorized use may result in legal action.</p>
                    </div>
                </div></div>
							
							</body>
</html>';
	}}

 $file_url = htmltodoc($html);

    // $html_content = wp_kses_post($html); // Sanitize HTML
   
    // $response = generate_doc_from_html($html_content);
    // $file_url = $response['file_url'];
    update_post_meta($post_id, 'document_url', $file_url);
    wp_send_json_success(['file'=>$file_url]);
    die;
    
    // Return response
    wp_send_json_success(['message' => 'Contract generated successfully!']);
}


function generate_doc_from_html($html) {
        $upload_dir = wp_upload_dir();
        $doc_folder = $upload_dir['path'];
        $doc_url = $upload_dir['url'];
    
        $filename = 'contract_' . time() . '.doc';
        $file_path = $doc_folder . '/' . $filename;
    
        // Open file for writing
        $file = fopen($file_path, 'w');
    
        // Write HTML content inside the file
        fwrite($file, "<html><head><meta charset='UTF-8'></head><body>");
        fwrite($file, $html);
        fwrite($file, "</body></html>");
    
        // Close the file
        fclose($file);
    
        return [
            'success' => true,
            'file_url' => $doc_url . '/' . $filename,
            'file_path' => $file_path
        ];
    }

    function get_category_name_list_columns($term_ids) {
        $names = [];
    
        foreach ($term_ids as $term_id) {
            $term = get_term($term_id);
            if (!is_wp_error($term) && !empty($term)) {
                $names[] = $term->name;
            }
        }
    
        // Initialize 3 columns
        $columns = [[], [], []];
        $i = 0;
    
        // Distribute names round-robin into 3 columns
        foreach ($names as $name) {
            $columns[$i % 3][] = $name;
            $i++;
        }
    
        // Start table
        $output = '<table style="width: 100%;"><tr>';
    
        foreach ($columns as $col) {
            $output .= '<td style="vertical-align: top; width: 33.33%;">';
            $output .= '<ul style="margin: 0; padding-left: 20px;">';
            foreach ($col as $name) {
                $output .= '<li>' . esc_html($name) . '</li>';
            }
            $output .= '</ul></td>';
        }
    
        $output .= '</tr></table>';
    
        return $output;
    }




    function search_client_leads() {
        if (!isset($_POST['search'])) {
            wp_send_json_error(['message' => 'No search query provided']);
        }
    
        $search_query = sanitize_text_field($_POST['search']);
        $current_user_id = get_current_user_id(); // Get the logged-in user ID
    
        $args = array(
            'post_type'      => 'client_leads',
            'posts_per_page' => 10,
            's'              => $search_query,
            'author'         => $current_user_id, // Filter by logged-in user
        );
    
        $query = new WP_Query($args);
    
        $results = [];
    
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = [
                    'id'    => get_the_ID(),  // Include post ID
                    'title' => get_the_title(),
                    'link'  => get_permalink(),
                ];
            }
            wp_send_json_success($results);
        } else {
            wp_send_json_error(['message' => 'No results found']);
        }
    
        wp_die();
    }
    add_action("wp_ajax_search_client_leads", "search_client_leads");
    add_action("wp_ajax_nopriv_search_client_leads", "search_client_leads");
    
    function handle_proposal_selection() {
        if (!isset($_POST['proposal_id'])) {
            wp_send_json_error(['message' => 'No proposal ID provided']);
        }
    
         $post_id = intval($_POST['proposal_id']);
       $serialized_data = get_post_meta($post_id, 'cat_data', true);
    $reaction = get_post_meta($post_id, 'reaction', true);
    $retrieved_data = unserialize($serialized_data);
                
    // Sort the data by 'level'
    usort($retrieved_data, function ($a, $b) {
        return $a['level'] <=> $b['level'];
    });
    
    // Initialize an array to group data by level
    $grouped_data = [];
    foreach ($retrieved_data as $catData) {
        if (isset($catData['level'])) {
            $grouped_data[$catData['level']][] = $catData;
        }
    }
    $grandTotal = 0; // Initialize grand total
    $loveTotal = 0;
    $likeTotal = 0;
    // Create rows for each level
    $StyleP = "";
    foreach ($grouped_data as $level => $entries) {
        $num_entries = count($entries);
        
        switch ($level) {
            case 1:
                $parent_cat = 108;
                $funbal_img = "";
                break;
            case 2:
                $parent_cat = 109;
                $funbal_img = "";
                break;
            case 3:
                $parent_cat = 110;
                $funbal_img = "";
                break;
        }
    
        // Output the first entry with rowspan
        $categoryName = get_category_name_by_term_id($entries[0]['cat_id']);
        $pdfTitle = get_term_meta($entries[0]['cat_id'], 'pdf_title', true);
        if(!empty($pdfTitle)){
            $categoryName = get_term_meta($entries[0]['cat_id'], 'pdf_title', true);
        }
        $level = $entries[0]['level'];
        $rating = $entries[0]['rating'];
        $price = get_term_meta($entries[0]['cat_id'], 'price', true);
        $sale_price = get_term_meta($entries[0]['cat_id'], 'sale_price', true);
        $totalP = "";
        $totalP1 = "";
        
        if (!empty($sale_price) && $sale_price != 0) {
            // Display the sale price
            $totalP = '$'.esc_html($sale_price);
            $totalP1 = esc_html($sale_price);
        } else {
            // Display the regular price
            $totalP = '$'.esc_html($price);
            $totalP1 = esc_html($price);
        }
        
        $grandTotal += floatval($totalP1);
        $rating_string = '';
        
        foreach ($rating as $valueimg) {
            if ($valueimg === 'love') {
                $loveTotal += floatval($totalP1);
                $rating_string .= '<img style="width:16px;margin-top:5px;" src="https://fourcorters.com/wp-content/themes/hello-child/image/red-heart.svg" alt="Love">';
            } elseif ($valueimg === 'like') {
                $likeTotal += floatval($totalP1);
                $rating_string .= '<img style="width:16px;margin-top:5px;" src="https://fourcorters.com/wp-content/themes/hello-child/image/green-like.svg" alt="Like">';
            }
        }
    
        $parent_cat_name = get_category_name_by_term_id($parent_cat);
        $parent_cat_name = str_replace("MOSTLY", "", $parent_cat_name);
        $parent_cat_name = trim($parent_cat_name);
        $hideClass = ""; 
     
    
        // Check for subcategories if cat_id is 97
        //if ($entries[0]['cat_id'] == 41) {
            $subcategories = get_categories(['taxonomy'=> 'services_category','parent' => $entries[0]['cat_id'], 'hide_empty' => false]);
          
        //}
    
        // Output remaining entries for the current level
        for ($i = 1; $i < $num_entries; $i++) {
            $categoryName = get_category_name_by_term_id($entries[$i]['cat_id']);
            $pdfTitle = get_term_meta($entries[$i]['cat_id'], 'pdf_title', true);
            if(!empty($pdfTitle)){
                $categoryName = get_term_meta($entries[$i]['cat_id'], 'pdf_title', true);
            }
            $level = $entries[$i]['level'];
            $rating = $entries[$i]['rating'];
            $price = get_term_meta($entries[$i]['cat_id'], 'price', true);
            $sale_price = get_term_meta($entries[$i]['cat_id'], 'sale_price', true);
            $totalP = "";
            $withtotal = "";
            
            if (!empty($sale_price) && $sale_price != 0) {
                // Display the sale price
                $totalP = '$'.esc_html($sale_price);
                $withtotal = esc_html($sale_price);
            } else {
                // Display the regular price
                $totalP = '$'.esc_html($price);
                $withtotal = esc_html($price);
            }
    
            $grandTotal += floatval($withtotal);
            $rating_string = "";
            
            foreach ($rating as $valueimg) {
                if ($valueimg === 'love') {
                    $loveTotal += floatval($withtotal);
                    $rating_string .= '<img style="width:16px;" src="https://fourcorters.com/wp-content/themes/hello-child/image/red-heart.svg" alt="Love">';
                } elseif ($valueimg === 'like') {
                    $likeTotal += floatval($withtotal);
                    $rating_string .= '<img style="width:16px;" src="https://fourcorters.com/wp-content/themes/hello-child/image/green-like.svg" alt="Like">';
                }
            }
           
        }
    }
    // if($reaction=="love_like"){
    //     $grandTotal = $likeTotal; 
    // }
    // if($reaction==="like"){
    //     $grandTotal = $loveTotal; 
    // } 
    
    $cortervalue = $grandTotal/25000;
    $singlecouter = 25000;
    $extra_courter = "";
    $totalinvestment = "";
    $cortervalue = 41;

switch (true) {
    case ($cortervalue >= 40):
        $extra_courter = 2;
        break;
    case ($cortervalue >= 20):
        $extra_courter = 1;
        break;
    case ($cortervalue >= 15):
        $extra_courter = 0.5;
        break;
    case ($cortervalue >= 10):
        $extra_courter = 0.5;
        break;
    default:
        $extra_courter = "";
}


    if(!empty($extra_courter)){
        $totalinvestment = $grandTotal+$singlecouter*$extra_courter;
    }
    
        wp_send_json_success(['grandTotal' => $grandTotal,'totalcouter'=>$cortervalue,'totalinvestment'=>$totalinvestment,'extra_courter'=>$extra_courter ]);
    }
    add_action("wp_ajax_proposal_selected", "handle_proposal_selection");
    add_action("wp_ajax_nopriv_proposal_selected", "handle_proposal_selection");

function delete_contract_lead() {
    if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
        wp_send_json_error(['message' => 'Invalid request']);
    }

    $post_id = intval($_POST['post_id']);

    // Check if the user has permission to delete this post
    if (!current_user_can('delete_post', $post_id)) {
        wp_send_json_error(['message' => 'You do not have permission to delete this record.']);
    }

    if (wp_delete_post($post_id, true)) {
        wp_send_json_success(['message' => 'Post deleted successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete post.']);
    }
}
add_action('wp_ajax_delete_contract_lead', 'delete_contract_lead');
add_action('wp_ajax_nopriv_delete_contract_lead', 'delete_contract_lead'); // Allow for non-logged-in users if needed
  
//update contract url
function update_final_contract_url() {
    // Validate AJAX request
    if (!isset($_POST['contract_id']) || !isset($_POST['contract_url'])) {
        wp_send_json_error("Missing required fields.");
    }

    $contract_id = intval($_POST['contract_id']);
    $contract_url = esc_url_raw($_POST['contract_url']); // Sanitize URL

    // Check if contract exists
    if (get_post_type($contract_id) !== 'contract_lead') {
        wp_send_json_error("Invalid contract ID.");
    }

    // Update post meta
    update_post_meta($contract_id, 'final_contract_url', $contract_url);

    wp_send_json_success("URL updated successfully.");
}
add_action("wp_ajax_update_final_contract_url", "update_final_contract_url");
add_action("wp_ajax_nopriv_update_final_contract_url", "update_final_contract_url"); // Enable for non-logged-in users


add_action('wp_ajax_fetch_contract_leads', 'fetch_contract_leads');
add_action('wp_ajax_nopriv_fetch_contract_leads', 'fetch_contract_leads');

function fetch_contract_leads() {
    $user_id = get_current_user_id();
    $limit = isset($_POST['length']) ? intval($_POST['length']) : 10;
    $offset = isset($_POST['start']) ? intval($_POST['start']) : 0;
    $draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;

    $args = [
        'post_type'      => 'contract_lead',
        'posts_per_page' => $limit,
        'offset'         => $offset,
        'author'         => $user_id,
    ];

    $query = new WP_Query($args);
    $total_posts = $query->found_posts;

    $data = [];
    
    while ($query->have_posts()) : $query->the_post(); 
        $post_id = get_the_ID();
        $company = get_post_meta($post_id, 'company', true);
        $date = get_post_meta($post_id, 'date', true);
        $document_url = get_post_meta($post_id, 'document_url', true);
        $final_url = get_post_meta($post_id, 'final_contract_url', true);

        if (empty($final_url)) {
            $final_url = $document_url;
        }

        $data[] = [
            'post_id'      => $post_id,
            'company'      => $company,
            'date'         => $date,
            'document_url' => $document_url,
            'final_url'    => $final_url,
        ];
    endwhile;

    wp_reset_postdata();

    // Send response in the required format
    wp_send_json([
        'draw'            => $draw,
        'recordsTotal'    => $total_posts,
        'recordsFiltered' => $total_posts,
        'data'            => $data,
    ]);
}

function fetch_contract_leads_cal($post_id) {
  
   $serialized_data = get_post_meta($post_id, 'cat_data', true);
$reaction = get_post_meta($post_id, 'reaction', true);
$retrieved_data = unserialize($serialized_data);
            
// Sort the data by 'level'
usort($retrieved_data, function ($a, $b) {
    return $a['level'] <=> $b['level'];
});

// Initialize an array to group data by level
$grouped_data = [];
foreach ($retrieved_data as $catData) {
    if (isset($catData['level'])) {
        $grouped_data[$catData['level']][] = $catData;
    }
}
$grandTotal = 0; // Initialize grand total
$loveTotal = 0;
$likeTotal = 0;
// Create rows for each level
$StyleP = "";
foreach ($grouped_data as $level => $entries) {
    $num_entries = count($entries);
    
    switch ($level) {
        case 1:
            $parent_cat = 108;
            $funbal_img = "";
            break;
        case 2:
            $parent_cat = 109;
            $funbal_img = "";
            break;
        case 3:
            $parent_cat = 110;
            $funbal_img = "";
            break;
    }

    // Output the first entry with rowspan
    $categoryName = get_category_name_by_term_id($entries[0]['cat_id']);
    $pdfTitle = get_term_meta($entries[0]['cat_id'], 'pdf_title', true);
    if(!empty($pdfTitle)){
        $categoryName = get_term_meta($entries[0]['cat_id'], 'pdf_title', true);
    }
    $level = $entries[0]['level'];
    $rating = $entries[0]['rating'];
    $price = get_term_meta($entries[0]['cat_id'], 'price', true);
    $sale_price = get_term_meta($entries[0]['cat_id'], 'sale_price', true);
    $totalP = "";
    $totalP1 = "";
    
    if (!empty($sale_price) && $sale_price != 0) {
        // Display the sale price
        $totalP = '$'.esc_html($sale_price);
        $totalP1 = esc_html($sale_price);
    } else {
        // Display the regular price
        $totalP = '$'.esc_html($price);
        $totalP1 = esc_html($price);
    }
    
    $grandTotal += floatval($totalP1);
    $rating_string = '';
    
    foreach ($rating as $valueimg) {
        if ($valueimg === 'love') {
            $loveTotal += floatval($totalP1);
            $rating_string .= '<img style="width:16px;margin-top:5px;" src="https://fourcorters.com/wp-content/themes/hello-child/image/red-heart.svg" alt="Love">';
        } elseif ($valueimg === 'like') {
            $likeTotal += floatval($totalP1);
            $rating_string .= '<img style="width:16px;margin-top:5px;" src="https://fourcorters.com/wp-content/themes/hello-child/image/green-like.svg" alt="Like">';
        }
    }

    $parent_cat_name = get_category_name_by_term_id($parent_cat);
    $parent_cat_name = str_replace("MOSTLY", "", $parent_cat_name);
    $parent_cat_name = trim($parent_cat_name);
    $hideClass = ""; 
 

    // Check for subcategories if cat_id is 97
    //if ($entries[0]['cat_id'] == 41) {
        $subcategories = get_categories(['taxonomy'=> 'services_category','parent' => $entries[0]['cat_id'], 'hide_empty' => false]);
      
    //}

    // Output remaining entries for the current level
    for ($i = 1; $i < $num_entries; $i++) {
        $categoryName = get_category_name_by_term_id($entries[$i]['cat_id']);
        $pdfTitle = get_term_meta($entries[$i]['cat_id'], 'pdf_title', true);
        if(!empty($pdfTitle)){
            $categoryName = get_term_meta($entries[$i]['cat_id'], 'pdf_title', true);
        }
        $level = $entries[$i]['level'];
        $rating = $entries[$i]['rating'];
        $price = get_term_meta($entries[$i]['cat_id'], 'price', true);
        $sale_price = get_term_meta($entries[$i]['cat_id'], 'sale_price', true);
        $totalP = "";
        $withtotal = "";
        
        if (!empty($sale_price) && $sale_price != 0) {
            // Display the sale price
            $totalP = '$'.esc_html($sale_price);
            $withtotal = esc_html($sale_price);
        } else {
            // Display the regular price
            $totalP = '$'.esc_html($price);
            $withtotal = esc_html($price);
        }

        $grandTotal += floatval($withtotal);
        $rating_string = "";
        
        foreach ($rating as $valueimg) {
            if ($valueimg === 'love') {
                $loveTotal += floatval($withtotal);
                $rating_string .= '<img style="width:16px;" src="https://fourcorters.com/wp-content/themes/hello-child/image/red-heart.svg" alt="Love">';
            } elseif ($valueimg === 'like') {
                $likeTotal += floatval($withtotal);
                $rating_string .= '<img style="width:16px;" src="https://fourcorters.com/wp-content/themes/hello-child/image/green-like.svg" alt="Like">';
            }
        }
       
    }
}

$cortervalue = $grandTotal/25000;
$singlecouter = 25000;
$extra_courter = "";
$totalinvestment = "";
switch (true) {
    case ($cortervalue >= 40):
        $extra_courter = 2;
        break;
    case ($cortervalue >= 20):
        $extra_courter = 1;
        break;
    case ($cortervalue >= 15):
        $extra_courter = 0.5;
        break;
    case ($cortervalue >= 10):
        $extra_courter = 0.5;
        break;
    default:
        $extra_courter = "";
}
if(!empty($extra_courter)){
    $totalinvestment = $grandTotal+$singlecouter*$extra_courter;
    $extraPrice = $singlecouter*$extra_courter;
}

    return [
        'grandTotal' => $grandTotal,
        'totalcouter' => $cortervalue,
        'totalinvestment' => $totalinvestment,
        'extra_courter' => $extra_courter,
        'extraPrice' => $extraPrice,
    ];
}
function getcourterPrice($grandTotal) {
    $cortervalue = $grandTotal / 25000;
    $singlecouter = 25000;
    $extra_courter = 0; // Set default value
    $totalinvestment = $grandTotal; // Assuming total investment is equal to grand total

    // Fixing switch statement using if-else (switch doesn't work well with conditions like this)
    if ($cortervalue >= 40) {
        $extra_courter = 2;
    } elseif ($cortervalue >= 20) {
        $extra_courter = 1;
    } elseif ($cortervalue >= 15) {
        $extra_courter = 0.5;
    } elseif ($cortervalue >= 10) {
        $extra_courter = 0.5;
    }

    return [
        'cortervalue' => $cortervalue,
        'singlecouter' => $singlecouter,
        'extra_courter' => $extra_courter,
        'totalinvestment' => $totalinvestment
    ];
}



require_once __dir__.'/vendor/autoload.php';
require 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;
use \ConvertApi\ConvertApi;

function htmltodoc($html) {
    if (isset($html)) {
        // Include DOMPDF or any other library
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true); // Enable the HTML5 parser for better CSS handling
    $post_id = $searcharray['post_id'];
    $dompdf = new Dompdf($options); 
	$dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $output = $dompdf->output();

    // Save the PDF or output directly
    $current_date = uniqid();

    // Define the file path with the date included in the filename
    $file_path = wp_upload_dir()['path'] . '/generated_pdf_'. $current_date . '.pdf';

    file_put_contents($file_path, $output);

    // Return the file URL to the AJAX call
    $file_url = wp_upload_dir()['url'] . '/generated_pdf_'. $current_date . '.pdf';
    update_post_meta($searcharray['post_id'], 'pdf_url', $file_url);
    update_post_meta($searcharray['post_id'], 'completed', 1);
    
        // ✅ Use correct method for ConvertAPI key
     ConvertApi::setApiCredentials('JRDRbE3NKX3pGFbqI6XrczfAsBmnAXuF');

       

        $html_file_path = $html_dir . '/test.html';
        file_put_contents($html_file_path, $html);

    
        try {
            // ✅ Convert HTML file to DOCX
            // $result = ConvertApi::convert('docx', [
                // 'File' => $html_file_path,
            // ], 'html');
			$result = ConvertApi::convert('docx', [
				'File' => $file_url,
			], 'pdf'
		);
		

            // ✅ Save DOCX to uploads/converted-docs
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['basedir'] . '/converted-docs';
            wp_mkdir_p($upload_path);

            $savedFiles = $result->saveFiles($upload_path);
			 if (file_exists($file_path)) {
               unlink($file_path);
                //echo 'HTML file deleted.<br>';
            }
            foreach ($savedFiles as $file) {
               // echo 'DOCX saved: ' . $file_url . '<br>';
                return $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file);
            }

            // ✅ Delete original HTML file after conversion
           

        } catch (Exception $e) {
            echo 'Conversion error: ' . $e->getMessage();
        }

        die();
    }
}






// Add field to Add Term form
add_action('services_category_add_form_fields', function () {
    ?>
    <div class="form-field">
        <label for="is_draft">Mark as Draft</label>
        <input type="checkbox" name="is_draft" id="is_draft" value="1" />
        <p class="description">Check this to mark the category as draft.</p>
    </div>
    <?php
});

// Add field to Edit Term form
add_action('services_category_edit_form_fields', function ($term) {
    $is_draft = get_term_meta($term->term_id, 'is_draft', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="is_draft">Mark as Draft</label></th>
        <td>
            <input type="checkbox" name="is_draft" id="is_draft" value="1" <?php checked($is_draft, 1); ?> />
            <p class="description">Check this to mark the category as draft.</p>
        </td>
    </tr>
    <?php
});

add_action('created_services_category', 'save_services_category_draft_meta');
add_action('edited_services_category', 'save_services_category_draft_meta');

function save_services_category_draft_meta($term_id) {
    $is_draft = isset($_POST['is_draft']) ? '1' : '0';
    update_term_meta($term_id, 'is_draft', $is_draft);
}

?>