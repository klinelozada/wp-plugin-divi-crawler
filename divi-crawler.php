<?php
/**
* Plugin Name: Divi website Crawler & Importer
* Plugin URI: https://caveim.com/
* Description: Ability to crawl url, and import it with Divi Structures
* Author: Kline @ CaveIM
* Author URI: https://caveim.com/
* License: GPLv2 or later
*/

define('ABSPATH',ABSPATH);
require_once('simple_html_dom.php');

class CAVEIMDEV_DIVI_CRAWLER {

	public function __construct()
	{
		add_action( 'admin_menu', array( &$this, 'divi_crawler_menu') );
	}

	public function divi_crawler_menu()
	{
		add_menu_page(
			__( 'Divi Crawler', 'divi-crawler' ),
			__( 'Divi Crawler', 'divi-crawler' ),
			'manage_options',
			'divi-crawler',
			array(&$this,'divi_crawler_page'),
			'dashicons-schedule',
			3
		);
	}

	public function divi_crawler_page()
	{
		?>
		<!-- Bootstrap -->
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
		<!-- Google Fonts -->
		<link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap" rel="stylesheet">
		<!-- Slim JS -->
		<link href="<?php echo plugin_dir_url( __FILE__ ).'assets/css/slim-select/1.25.0/slimselect.min.css'; ?>" rel="stylesheet"></link>
		<style>
			.scraper-container{ 
				font-family: 'Poppins', sans-serif;
			}
			.scraper-container h3 {
				letter-spacing:-1px;
			}
			.status-style {
				text-transform:capitalize;
				margin:25px 0 15px;
				border: 1px solid #dcdee2 !important;
				width:100%;
				max-width: 100% !important;
				font-size: 1rem !important;
			}
			input[type=text].form-control {
				border:1px solid #dcdee2 !important;
			}
			.btn {
				padding:0.675rem 1.75rem;
				border-radius:2px;
			}
		</style>

		<div class="container-fluid scraper-container">

			<div class="row" style="margin-top:25px;margin-bottom:15px;">
				<div class="col-lg-12 col-md-12">
					<h3>Welcome to the Divi Crawler</h3>
					<hr>
				</div>
			</div>

				<div class="row">

					<div class="col-lg-6 col-md-6">

						<form action="" method="POST">

							<div class="form-group">
								<label for="urls" style="vertical-align: top;"><i class="fas fa-cogs"></i> Main Setting</label>
								<?php $this->divi_get_all_pages(); ?>
								<?php $this->divi_get_publish_status(); ?>
							</div>

							<div class="form-group" style="display:none;">
								<label for="urls" style="vertical-align: top;"><i class="fas fa-route"></i> Internal to External Redirection</label>
								<br/>
								<small>You can only select a <strong>Parent</strong> from the Main Setting</small>
								<input type="text" name="redirect-title" id="redirectTitle" class="form-control" placeholder="Title :: Olympic Sports" style="margin-top:15px;">
								<input type="text" name="redirect-slug" id="redirectSlug" class="form-control" placeholder="Slug :: olympic-sports123" style="margin-top:15px;">
								<input type="text" name="redirect-url" class="form-control" placeholder="External URL :: http://externaldomain.com" style="margin-top:15px;">
							</div>

							<div class="form-group" style="margin-top:25px;">
								<label for="urls" style="vertical-align: top;"><i class="fas fa-brackets-curly"></i> URLS</label>
								<textarea name="urls" id="urls" cols="30" rows="20" class="form-control"></textarea>
								<small class="form-text text-muted" style="text-align:right;">One row per site</small>
							</div>

							<div class="form-group" style="display:none;">
								<input type="checkbox" name="row_module" value="1" class="form-control">
								<label for="">Add row on each module?</label>
							</div>
							<div class="form-group" style="display:none;">
								<input type="checkbox" name="header_slider" value="1" class="form-control">
								<label for="">Header Contains Slider?</label>
							</div>
							<div class="form-group" style="display:none;">
								<input type="checkbox" name="debug" value="1" class="form-control">
								<label for="">Debug</label>
							</div>
							<div class="form-group">
								<button type="submit" class="btn btn-primary" name="crawl"><i class="fas fa-repeat"></i> Crawl URL</button>
								<button type="submit" class="btn btn-warning float-right" name="create_redirect" style="display:none;"><i class="fas fa-route"></i> Create Redirect</button>
							</div>
						</form>

					</div><!-- .col-lg-8.col-md-8 -->

					<!-- 
						<script>
							jQuery(function($) {
							    $('#redirectTitle').on('keyup', function() {
							    	var redirecttitle = $(this).val().toLowerCase().replace(" ","-");
							        $('#redirectSlug').val(redirecttitle);
							    });
							});
						</script>
					-->

					<div class="col-lg-6 col-md-6">

						<table class="table table-bordered" style="font-size:12px;">
						<thead class="thead-dark">
							<tr>
								<th>Title</th>
								<th>Old URL</th>
								<th>New URL</th>
								<th>Redirect URL</th>
							</tr>
						</thead>
						<?php
								$row_module = $_POST['row_module'];
								$debug = $_POST['debug'];

								if(!$debug){
									$debug = 0;
								}

								if(isset($_POST['crawl'])):
									$urls 	= explode( "\r\n", $_POST['urls'] );
									$titles = explode( "\r\n", $_POST['titles'] );
									$parent_id = $_POST['page_id'];
									$status = $_POST['status'];
									// $data = array_combine($titles, $urls);
									foreach($urls as $url) {
										$url = str_replace('https','http',$url);
										$this->divi_crawl_url_v2($parent_id,$status,$url,$row_module,$debug);
										// $this->divi_crawl_url_debug($url,$row_module,$debug);
									}
								endif;

								if(isset($_POST['create_redirect'])):

									$redirect_pID 	= $_POST['page_id'];
									$redirect_title = $_POST['redirect-title'];
									$redirect_slug 	= $_POST['redirect-slug'];
									$redirect_url 	= $_POST['redirect-url'];

									// print_r( $redirect_pID . ' ' . $redirect_title . ' ' . $redirect_slug . ' ' . $redirect_url);

									$this->divi_redirect_only($redirect_pID, 'publish', $redirect_title, $redirect_slug, $redirect_url);

								endif;
						?>

						<?php if(!isset($_POST['crawl']) || !isset($_POST['crawl_redirect'])): ?>
							<tbody>
								<tr>
									<td colspan="4">Results will show here</td>
								</tr>
							</tbody>
						<?php endif; ?>


						</table>
					</div><!-- .col-lg-8.col-md-8 -->

				</div><!-- .row -->

		</div><!-- .container -->
		<!-- SlimJS -->
		<script src="<?php echo plugin_dir_url( __FILE__ ).'assets/js/slim-select/1.25.0/slimselect.min.js'; ?>"></script>
		<!-- Font Awesome Pro Kit -->
		<script src="https://kit.fontawesome.com/f4bc91b179.js" crossorigin="anonymous"></script>
		<!-- Bootstrap JS -->
		<!-- <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script> -->
		<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
		<script>
			new SlimSelect({
			  select: '#page_id'
			})
		</script>
		<?php
	}

	private function divi_redirect_only($redirect_pID, $redirect_status, $redirect_title, $redirect_slug, $redirect_url)
	{
		$redirect_content= '';

		$redirect_pageID = $this->divi_insert_post($redirect_pID, $redirect_status, $redirect_title, $redirect_content, $redirect_slug);

		$redirect_postSlug = get_post_field( 'post_name', $redirect_pageID );
		$redirect_postLink = esc_url( get_permalink($redirect_pageID) );

		$redirect_url_parse = wp_parse_url( $redirect_postLink );
		$redirect_url_path = $redirect_url_parse['path'];

		$this->divi_redirect_url($redirect_url_path, $redirect_url);

		$redirect_html = '<tbody>';
		$redirect_html .= '<tr>';
			$redirect_html .= '<td><strong style="color:#343A40;">'.$redirect_title.'</strong></td>';
			$redirect_html .= '<td><a href="'.get_site_url().'/'.$redirect_postSlug.'" target="_blank">'.get_site_url().'/'.$redirect_postSlug.'</a></td>';
			$redirect_html .= '<td><a href="'.$redirect_postLink.'" target="_blank">'.$redirect_postLink.'</a></td>';
			$redirect_html .= '<td><a href="'.$redirect_url.'" target="_blank">'.$redirect_url.'</a></td>';
		$redirect_html .= '</tr>';
		$redirect_html .= '</tbody>';

		print_r($redirect_html);
	}

	private function divi_crawl_url_v2($parent_id,$status,$url,$row_module,$debug)
	{
		if(!$parent_id)
			$parent_id = 0;

		if(!$status)
			$status = 0;

		// Start :: Process Curl
	    $url = filter_var($url, FILTER_SANITIZE_URL);
	    $ch = curl_init();
	    $timeout = 5;
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    $html = curl_exec($ch);
	    curl_close($ch);

	    // Remove the sidebar
	    $html = preg_replace('/<div id="subCol">(.*)<\/div>/s', '', $html);
	    $html = preg_replace('/<div class="large-3 columns sidebar">(.*)<\/div>/s', '', $html);
	    
	    // Process Title  -> Problem, so create a different function that could load the titles before entering foreach
	    $titleraw 	= preg_match("/\<title.*\>(.*)\<\/title\>/isU", $html, $matches);
        $titleraw	= $this->multiexplode( array("/","|"), $matches[1] );
        $title 		= $titleraw[0]; // Title

        // Check if page is scrapable 
        if($title == 'Object moved'){
        	$login_require_check = 1;
        }

        // Process Slug 
        $url_main_parse = parse_url($url);
        $url_slug = $url_main_parse['path'];

		// Replace Comments to Sections
	    $html = preg_replace('/<!-- Start_Content_(\d+) -->/i', '<section class="lcms_content_block" id="${1}">', $html);
	    $html = preg_replace('/<!-- End_Content_(\d+) -->/i', '</section><!-- end ID ${1} -->', $html);
	    // Replace Headers outside to textElements
	    $html = preg_replace('/<div id="main_1_pnlTitle" class="container">(.*)<\/div>/s', '<div class="textElement">$1</div>', $html);
	    $html = preg_replace('/<div id="sidebar_4_pnlTitle" class="container">(.*)<\/div>/s', '<div class="textElement">$1</div>', $html);
		// Getting post
		$html = preg_replace('/<div class="postBody">(.*)<\/div>/s', '<div class="textElement">$1</div>', $html);
		
	    // Load DOM
	    $dom = new DOMDocument();
	    @$dom->loadHTML($html);
	    libxml_use_internal_errors (true);

		// Get all contents under 'section'
	    $complete_content = $dom->getElementsByTagName('section');

	    // Value Preparations
	    $content = "";
        $count = 0;
    	$content_gallery_fid = array();

    	// Divi Conversion (Opening and Closing)
	    if($row_module == 0) {
		    $divi_section_opening = '[et_pb_section fb_built="1" admin_label="section" _builder_version="3.22"][et_pb_row _builder_version="4.0.6"][et_pb_column _builder_version="4.0.6" type="4_4"]';
		    $divi_section_closing = '[/et_pb_column][/et_pb_row][/et_pb_section]';
	    } else {
	    	$divi_section_opening = '[et_pb_section fb_built="1" admin_label="section" _builder_version="3.22"]';
	    	$divi_section_closing = '[/et_pb_section]';
	    }

	    // DOM Slicing
		$i = 0;

		// Slider Process
	    foreach($complete_content as $section_slider) {
    		if(strpos($dom->saveXML($section_slider), '"SlideShowContainer"')) {
    			if(strpos($dom->saveXML($section_slider), '<script')) {
    				$withSlider = 1;
    			}
    		}
		}
		
		if($withSlider == 1) {
			$slider_group = $this->get_slider_images_v2($url);
			$slider_content = '';
			foreach($slider_group as $key => $sgd_img) {
				$img_arr = array();
				foreach($sgd_img as $img) {
					$img_arr[] = $img;
				}
				$slider_content .= $this->divi_reupload_img_slider($url,$img_arr);
				// var_dump($slider_group);
			}
		}

		// print_r($slider_content);
	    foreach($complete_content as $section) {

	    	// Javascript removal
			while (($remove_script = $dom->getElementsByTagName("script")) && $remove_script->length) {
	            $remove_script->item(0)->parentNode->removeChild($remove_script->item(0));
		    }

		    // Default :: Divi Row Opening (for 1 whole row only)
		    if($row_module == 1) {
		    	$content .= '[et_pb_row _builder_version="4.0.6"][et_pb_column _builder_version="4.0.6" type="4_4"]';
		    }

		    // DOM Content Slicing
	    	if(strpos($dom->saveXML($section), '"imageElement"')){
				$content .= $this->divi_section_v2($url, 'imageElement', $dom->saveXML($section));
	    	}

	    	if(strpos($dom->saveXML($section), '"SlideShowContainer"')) {
	    		$content = $slider_content;
	    	}

	    	if(strpos($dom->saveXML($section), '"textElement"') || strpos($dom->saveXML($section),'"eventsElement"') ) {
				$content .= $this->divi_section_v2($url, 'textElement', preg_replace("/&#?[a-z0-9]{2,8};/i", '', $dom->saveXML($section)));
	    	}

			if(strpos($dom->saveXML($section), '"linksModule"')) {
				$content .= $this->divi_section_v2($url, 'linksModule', preg_replace("/&#?[a-z0-9]{2,8};/i", '', $dom->saveXML($section)));
	    	}

	    	if(strpos($dom->saveXML($section), '"codeElement"')) {
				$content .= $this->divi_section_v2($url, 'codeElement', $dom->saveXML($section));
	    	}

	    	if(strpos($dom->saveXML($section), '"imageElement photoGalleryElement"')) {
	    		// Check if it's a slider or not
	    		if(!strpos($dom->saveXML($section), '"SlideShowContainer"')) {
					$match_id = $this->divi_get_the_gallery_id($dom->saveXML($section));
					$content .= $this->divi_reupload_img_gallery($url, $this->get_gallery_images_v2($match_id,$url), 0, 0);
	    		}
			}

			// DEBUG :: Write your debug here
			// DEBUG :: ======================= START ===============================

			// DEBUG :: ======================= E N D ===============================
			// DEBUG :: Write your debug here :: END

			// Default :: Divi Row Closing (for 1 whole row only)
	    	if($row_module == 1) {
	    		$content .= '[/et_pb_column][/et_pb_row]';
	    	}

		}
		// Combine DOM Slicing and DIVI Opening and Closing
		$content = $divi_section_opening . $content . $divi_section_closing;

	    if($debug == 1) {
	    	print_r("<br/><textarea style='width:700px;height:500px;'>".$content."</textarea>");
	    } else {

    		// Check if the page requires login
	    	if($login_require_check == 1){
	    		$parent_id  = 0;
	    		$status 	= 'draft';
	    		$title 		= 'Login Required';
	    		$content 	= 'This URL needs a login auth: '.$url;
	    	}

	    	// Insert into post and get the ID
	    	// DEBUG :: Comment this if you're debugging 

			$postID = $this->divi_insert_post($parent_id,$status,$title,$content,$url_slug); // <----------- IMPORTANT TO UNQUOTE 

			// If posts is publish and if not show Post ID
			if($status == 'publish') {

    			$postSlug = get_post_field( 'post_name', $postID );
    			$postLink = esc_url( get_permalink($postID) );

    			$url_parse = wp_parse_url( $postLink );
    			$url_path = $url_parse['path'];

    			$this->divi_redirect_url('/'. $postSlug, $url_path);

    			$res_list = '<tbody>';
    			$res_list .= '<tr>';
    				$res_list .= '<td><strong style="color:#343A40;">'.$title.'</strong></td>';
    				$res_list .= '<td><a href="'.$url.'" target="_blank">'.$url.'</a></td>';
    				// $res_list .= '<td><strong><a href="'.$postLink.'" style="color:#343A40;">'.$title.'</a></strong><br/><small>'.$postLink.'</small></td>';
    				$res_list .= '<td><a href="'.get_site_url().'/'.$postSlug.'" target="_blank">'.get_site_url().'/'.$postSlug.'</a></td>';
    				$res_list .= '<td><a href="'.$postLink.'" target="_blank">'.$postLink.'</a></td>';
    			$res_list .= '</tr>';
    			$res_list .= '</tbody>';
				
				// print_r('<strong><a href="/'.$postSlug.'">'.$title.'</a></strong> page has been created!');

			} else {

    			$res_list = '<tbody>';
				$res_list .= '<tr>';
    				$res_list .= '<td><strong style="color:#343A40;">'.$title.'</strong></td>';
					$res_list .= '<td>'.$url.'</td>';
					// $res_list .= '<td><strong><a href="/?page_id='.$postID.'&preview=true">'.$title.'</a></strong> page has been created!<br/><small>/?page_id='.$postID.'&preview=true</small></td>';
					$res_list .= '<td>'.get_site_url().'/?page_id='.$postID.'&preview=true</td>';
					$res_list .= '<td>Drafted</td>';
				$res_list .= '</tr>';
    			$res_list .= '</tbody>';

				// print_r('<strong><a href="/?page_id='.$postID.'&preview=true">'.$title.'</a></strong> page has been created!<br/>');
			}
	    }
	    print_r($res_list);
	}

	private function divi_get_the_gallery_id($data)
	{
		if(strpos($data,'"lightbox[')){
			$data = trim(preg_replace('/\s+/', ' ', $data));
			$data = str_replace("\r\n","",$data);
			$data = preg_replace("/<\/?div[^>]*\>/i", "", $data); 
			$re = '~<a(.*?)rel="([^"]+)"(.*?)>~';
			preg_match($re, $data, $matches, PREG_OFFSET_CAPTURE, 0);
			$data = str_replace('lightbox[','',$matches[2][0]);
			$data = str_replace(']','',$data);
			return $data;
		}
	}

	private function divi_section_v2($url, $class, $data)
	{
		$divi_converted = $this->get_actual_element_content($url,$class,$data);			
		return $divi_converted;
	}

	private function get_actual_element_content($url, $class, $data)
	{
		/* If image, just go directly to the case*/

		/* If texts, process what tags it used and use it as $element*/
		$data = trim(preg_replace('/\s\s+/', ' ', $data));
		switch($class) {
			case "codeElement":
				$code = $this->get_actual_code_embed($data);
				$html = '[et_pb_video _builder_version="4.0.6" src="'.$code.'" hover_enabled="0"][/et_pb_video]';
				break;
			case "textElement":
				$text = $this->get_actual_texts_v2($url, $data);
				$html = '[et_pb_text _builder_version="4.0.6"]'.$text.'[/et_pb_text]';
				break;
			case "linksModule":
				$links = $this->get_actual_links($url, $data);
				$html = '[et_pb_text _builder_version="4.0.6"]'.$links.'[/et_pb_text]';
				break;
			/* Image has to be processed, get the real url, and reupload the image to the media */
			case "imageElement":
				$url = $this->get_base_url($url);
				$image_url = $this->divi_reupload_img($url . $this->get_actual_img_url_v2($data));
				$html = '[et_pb_image _builder_version="4.0.6" src="'.$image_url.'" hover_enabled="0"][/et_pb_image]';
				break;
			default:
		}
		return($html);

		/* Have to identify the textfield if what html tag it's using */

	}

	private function get_base_url($url) {
		$url_b = parse_url($url);
		$url_base = $url_b['scheme'].'://'.$url_b['host'].'/';
		return $url_base;
	}

	private function get_path_url($url) {
		$url_b = parse_url($url);
		$url_path = '/'.$url_b['path'];
		return $url_path;
	}

	// Get actual texts
	private function get_actual_texts_v2($url, $text_data){
		$text = strip_tags($text_data,'<h1><h2><h3><h4><h5><h6><p><a><br><img><ul><li><ol><table><thead><th><tbody><tr><td><iframe>');

		// Iframe process only youtube
		if (($pos = strpos($text, "iframe")) !== FALSE) {
			$text = trim(preg_replace('/\s\s+/', ' ', $text));

			if(preg_match('/<*iframe[^>]*src *= *["\']?([^"\']*)/m', $text, $iframe_match) !== FALSE){
				$ifrm = urldecode($iframe_match[1]);
				if(strpos($iframe_match[1], "youtube") || strpos($iframe_match[1], "vimeo") || strpos($iframe_match[1], "youku")){
					$realText = str_replace("$iframe_match[1]", "$ifrm", $text);
					$realText = str_replace("//cdn.embedly.com/widgets/media.html?src=", "", $realText);
					// print_r('<textarea name="" id="" cols="30" rows="10">'.$realText.'</iframe></textarea>');
					return $realText.'</iframe>';
				}
			}
		}

		if(($img_trig = strpos($text, "a")) !== FALSE){

				if (($pos = strpos($text, "a")) !== FALSE) {

					$url = $this->get_base_url($url);

				    if(preg_match_all('/<*a[^>]*href *= *["\']?([^"\']*[.pdf\.xlsx\.xls\.csv\.doc\.docx])/', $text, $pdf_matches) !== FALSE) {
						foreach($pdf_matches as $pdfs){	
							$original_pdfs = $pdfs;
						}
						$reupload_pdf = array();
						foreach($original_pdfs as $original_pdf) {
							if(strpos($original_pdf, "maps") !== FALSE) {
								$reupload_pdf[] = $original_pdf;
							} else {
								if(strpos($original_pdf, '.jpg') || strpos($original_pdf,'.png') || strpos($original_pdf,'.jpeg') || strpos($original_pdf,'.gif') || strpos($original_pdf,'.svg') || strpos($original_pdf,'.pdf') || strpos($original_pdf,'.xlsx') || strpos($original_pdf,'.xls') || strpos($original_pdf,'.csv') || strpos($original_pdf,'.doc') || strpos($original_pdf,'.docx')) {
									$reupload_pdf[] = $this->divi_reupload_img($url . $original_pdf);
								} else {
									$reupload_pdf[] = $original_pdf;
								}
							}
						}

						// Replace the old urls with the new reuploaded urls
						$pdf_text = str_replace($original_pdfs,$reupload_pdf,$text);
						$pdf_text = preg_replace('/class=".*?"/', '', $pdf_text);
						$pdf_text = preg_replace('/style=".*?"/', '', $pdf_text);

						// Process the images
					    if(preg_match_all('/<*img[^>]*src *= *["\']?([^"\']*)/m', $pdf_text, $matches) !== FALSE) {
					    	$original_images = array();
							foreach($matches as $imgs){
								foreach($imgs as $img){
									// Remove the <img>
									if (($img_pos = strpos($img, "img")) == FALSE) {
										$original_images[] = $img;
									}
								}
							}
							$reupload_img = array();
							foreach($original_images as $original_image) {
								$reupload_img[] = $this->divi_reupload_img($url . $this->get_path_url($original_image));
							}

							$image_text = str_replace($original_images,$reupload_img,$pdf_text);
							$image_text = preg_replace('/class=".*?"/', '', $image_text);
							$image_text = preg_replace('/style=".*?"/', '', $image_text);
					    }

				    }
			        return $image_text;
				}

			} 

			if(($img_trig = strpos($text, "img")) !== FALSE){
				// Process if the there's an image in the textblock
				$url = $this->get_base_url($url);
				if (($pos = strpos($text, "img")) !== FALSE) {
				    if(preg_match_all('/<*img[^>]*src *= *["\']?([^"\']*)/m', $text, $matches) !== FALSE) {

				    	// Get all the original urls
				    	$original_images = array();
						foreach($matches as $imgs){
							foreach($imgs as $img){
								// Remove the <img>
								if (($img_pos = strpos($img, "img")) == FALSE) {
									$original_images[] = $img;
								}
							}
						}

						// Get all the reuploaded images
						$reupload_img = array();
						foreach($original_images as $original_image) {
							$reupload_img[] = $this->divi_reupload_img($url . $this->get_path_url($original_image));
						}

						// Replace the old urls with the new reuploaded urls
						$new_text = str_replace($original_images,$reupload_img,$text);
						$new_text = preg_replace('/class=".*?"/', '', $new_text);
						$new_text = preg_replace('/style=".*?"/', '', $new_text);

						return $new_text;

				    }
				}
			} 
	}

	private function get_actual_links($url, $text_data){

		$text = strip_tags($text_data,'<h1><h2><h3><h4><h5><h6><p><a><br><img><ul><li><ol><table>');

		if(($img_trig = strpos($text, "a")) !== FALSE){

			if (($pos = strpos($text, "a")) !== FALSE) {

				$url = $this->get_base_url($url);

			    if(preg_match_all('/<*a[^>]*href *= *["\']?([^"\']*[.pdf\.xlsx\.xls\.csv\.doc\.docx])/', $text, $pdf_matches) !== FALSE) {
					foreach($pdf_matches as $pdfs){	
						$original_pdfs = $pdfs;
					}
					$reupload_pdf = array();
					foreach($original_pdfs as $original_pdf) {
						if(strpos($original_pdf, "maps") !== FALSE) {
							$reupload_pdf[] = $original_pdf;
						} else {
							if(strpos($original_pdf, '.jpg') || strpos($original_pdf,'.png') || strpos($original_pdf,'.jpeg') || strpos($original_pdf,'.gif') || strpos($original_pdf,'.svg') || strpos($original_pdf,'.pdf') || strpos($original_pdf,'.xlsx') || strpos($original_pdf,'.xls') || strpos($original_pdf,'.csv') || strpos($original_pdf,'.doc') || strpos($original_pdf,'.docx') ) {
								$reupload_pdf[] = $this->divi_reupload_img($url . $original_pdf);
							} else {
								$reupload_pdf[] = $original_pdf;
							}
						}
					}

					// Replace the old urls with the new reuploaded urls
					$pdf_text = str_replace($original_pdfs,$reupload_pdf,$text);
					$pdf_text = preg_replace('/class=".*?"/', '', $pdf_text);
					$pdf_text = preg_replace('/style=".*?"/', '', $pdf_text);

					// Process the images
				    if(preg_match_all('/<*img[^>]*src *= *["\']?([^"\']*)/m', $pdf_text, $matches) !== FALSE) {
				    	$original_images = array();
						foreach($matches as $imgs){
							foreach($imgs as $img){
								// Remove the <img>
								if (($img_pos = strpos($img, "img")) == FALSE) {
									$original_images[] = $img;
								}
							}
						}
						$reupload_img = array();
						foreach($original_images as $original_image) {
							$reupload_img[] = $this->divi_reupload_img($url . $this->get_path_url($original_image));
						}

						$image_text = str_replace($original_images,$reupload_img,$pdf_text);
						$image_text = preg_replace('/class=".*?"/', '', $image_text);
						$image_text = preg_replace('/style=".*?"/', '', $image_text);
				    }

			    }
		        return $image_text;
			}

		}
	}

	private function get_actual_code_embed($code_data){
		if (($pos = strpos($code_data, "codeElement")) !== FALSE) { 
			$code = substr($code_data, $pos+0);
			$code = str_replace('codeElement">', "", $code);
			$code = str_replace('</div>', "", $code);
			$code = str_replace('</section>', "", $code);
			$code = preg_match('/src="([^"]+)"/', $code, $match);
			$code = $match[1];
			$code = strtok($code, '?');
			$code = str_replace("/embed/", '/watch?v=', $code);
			return $code;
		}
	}

	private function get_actual_img_url_v2($data)
	{
		if (($pos = strpos($data, "/ResizeImage.aspx?img=%2F")) !== FALSE) { 
			$image_url = substr($data, $pos+0);
			$image_url = str_replace("/ResizeImage.aspx?img=%2F", "", $image_url);
		    $image_url = strtok($image_url, '&');
			$image_url = str_replace("%2F", "/", $image_url);
			return $image_url;
		}
	}

	private function divi_crawl_element($url, $el = NULL, $el_request = NULL, $debug)
	{
		$data = array();
		$data[$el_request] = $el_request;
		foreach($el as $par) {
			if($el_request == 'img'){
				if(!empty($par->getAttribute('src'))){
					if(strpos($par->getAttribute('src'), '.aspx') !== false){
						if($debug == 1) {
	        				$data[] = $url . $this->get_actual_img_url($par->getAttribute('src'));
						} else {
							$data[] = $this->divi_reupload_img($url . $this->get_actual_img_url($par->getAttribute('src')));
						}
					} elseif(strpos($par->getAttribute('src'), 'png') !== false || strpos($par->getAttribute('src'), 'jpg') !== false ) {
						if($debug == 1) {
	        				$data[] = $url . $par->getAttribute('src');
						} else {
	        				$data[] = $this->divi_reupload_img($url . $par->getAttribute('src'));
						}
					}
				}
			} elseif($el_request == 'a') {
				if(!empty($par->getAttribute('href'))){
					$link_text = trim($par->textContent);
        			$data[] = "<a href='".$par->getAttribute('href')."'>".$link_text."</a>";
				}
			} else {
				if($count !== 1) {
					if(!empty(trim($par->textContent))){
	        			$data[] = trim($par->textContent);
					}
				}
			}
		}
		return $data;
	}

	private function get_actual_img_url($image_url)
	{
		$strings = array('/ResizeImage.aspx?img=');
		$image_url = str_replace($strings, "", $image_url);
		$image_url = str_replace("%2F", "/", $image_url);
		$image_url = strstr($image_url, '&w=', true);
		$image_url = str_replace($args->query, "", $image_url);

		return $image_url;
	}

	private function get_script_image_slider($url){

		$url = $url;
		libxml_use_internal_errors(true); 
		$doc = new DOMDocument();
		$doc->loadHTMLFile($url);
		$xpath = new DOMXpath($doc);
		$elements = $xpath->query('//body//script[not(@src)]');
		$html = '';
		foreach ($elements as $tag) {
			if (($pos = strpos($tag->nodeValue, "&img=%2f")) !== FALSE) { 
			    $whatIWant = substr($tag->nodeValue, $pos+8);
			    $result = strtok($whatIWant, '\"";');
			    $result = strtok($result, '&');
				$result = str_replace("%2f", "/", $result);
				$image_url = str_replace("+", " ", $result);
			    return($image_url);
			}
		}
	}

	private function get_slider_images($url)
	{
		// Create DOM from URL or file
		$html = file_get_html($url);
		$scripts = $html->find('script');

		/* Process the script */
		$data = '';
	    foreach($scripts as $s) {
	        if(strpos($s->innertext, '&img=%2f') !== false) {
	        	$s = str_replace(' ','',$s->innertext);
	        	$data .= $s;
	        }
	    }

	    /* Get only the images */
	    $re = '/"&.*?"/m';
	    if(preg_match_all($re, $data)) {
		    preg_match_all($re, $data, $matches, PREG_SET_ORDER, 0);
	    }
	    // print_r($matches);

	    $image_gallery = array();
	    foreach($matches as $arr_images){
	    	foreach($arr_images as $image){
	    		if($image !== '"&"'){
	    			$image = str_replace('"&img=%2f','',$image);
	    			$image = str_replace('%2f','/',$image);
	    			$image = str_replace('+',' ',$image);
			    	$image = strtok($image, '&');
	    			$image_gallery[] = $image;
	    		}
	    	}
	    }

	    return $image_gallery;
	}

private function get_slider_images_v2($url) {

		$html = file_get_html($url);
		$scripts = $html->find('script');

		/* Process the script */
		$data = '';
	    foreach($scripts as $s) {
	        if(strpos($s->innertext, '&img=%2f') !== false) {
	        	$s = str_replace(' ','',$s->innertext);
	        	$s = trim($s);
	        	$s = preg_replace('/\s+/', '', $s);
	        	$data .= $s;
	        }
	    }
	    // print_r('<textarea cols="100" rows="100">'.$data.'</textarea>');

        // Of all the id listed, there's only 2 unique, get it
    	$re = '/SlideShow(\d+)/m';
    	$data_2 = array();
        if(preg_match_all($re, $data, $matches, PREG_SET_ORDER, 0) !== FALSE){
        	foreach($matches as $match){
        		foreach($match as $mx){
        			$data_2[] = $mx;
        		}
        	}
        }
        $slide_ids = array_unique($data_2);

        // Show the one with the text 'SlideShow'
        $final_slide_ids = array();
        foreach($slide_ids as $slide_id){
        	if(strpos($slide_id, 'SlideShow') !== FALSE ){
        		$final_slide_ids[] = $slide_id;
        	}
        }
        // print_r($final_slide_ids);

        // Let's find the ID inside the script itself get it *from and *to
        $xda = '';
		$image_gallery = array();
        foreach($final_slide_ids as $final_slide_id){

    		// print_r($final_slide_id);
    		$re_format = '/'.$final_slide_id.'.SetContents=function\(\){(.*?)'.$final_slide_id.'.contents.push\(content\);}/m';
        	if(preg_match_all($re_format, $data, $find_matches_1, PREG_SET_ORDER, 0) !== FALSE){
        		foreach($find_matches_1 as $find_matches_2) {
        			foreach($find_matches_2 as $find_matches_3) {
        				$xda .= $find_matches_3;

        				// Filter to the bones
        				if(strpos($find_matches_3,'varimageContainer') == FALSE){
        				
        					// print_r('<textarea cols="100" rows="10">'.$find_matches_3.'</textarea>');

    					    $re = '/"&.*?"/m';
    					    if(preg_match_all($re, $find_matches_3)) {
    						    preg_match_all($re, $find_matches_3, $matches, PREG_SET_ORDER, 0);
    					    }

        					// print_r('<textarea cols="100" rows="10">'.$matches.'</textarea>');

        					// Find if there are images in the $matches if not then use the other formula
        					$find_imgs_unq = array_unique($matches);
        					foreach($find_imgs_unq as $find_img_unq) {
    							foreach($find_img_unq as $img_unq) {
    								// If &img= is not found use a different formula
	        						if(strpos($img_unq,'&img=') == FALSE) {

    									    $re = '/&img=.*?"/m';
    									    if(preg_match_all($re, $find_matches_3)) {
    										    preg_match_all($re, $find_matches_3, $matches, PREG_SET_ORDER, 0);
    									    }

	        						}
    							}
        					}

    					    $re_div_id = '/varimageContainer=LCMS.jq\("(.*?)"\);/m';
    					    if(preg_match_all($re_div_id, $find_matches_3)) {
    					    	preg_match_all($re_div_id, $find_matches_3, $matches_div_id, PREG_SET_ORDER, 0);
    					    	// print_r($matches_div_id[0]);

    					    	foreach($matches_div_id[0] as $div_id){
        					    	$div_id = str_replace('#','',$div_id);
    					    	}
    					    }
        					// print_r($matches);

    					    // Final Touch
					        foreach($matches as $arr_images){
					        	foreach($arr_images as $image){
					        		if($image !== '"&"'){
					        			$image = str_replace('"&img=%2f','',$image);
					        			$image = str_replace('%2f','/',$image);
					        			$image = str_replace('+',' ',$image);
					        			$image = str_replace('img=/','',$image);
					    		    	$image = strtok($image, '&');
					    		    	$image_gallery[] = array('slider_id' => $final_slide_id, 'div_id' => $div_id , 'image_url' => $image);
					        		}
					        	}
					        }
        				}
        			}
        		}
        	}
        }

		// print_r($image_gallery);

        // Group them by slider_id
		$slider_group = array();
		foreach ($image_gallery as $img_slider) {
		    $slider_group[$img_slider['slider_id']][] = $img_slider['image_url'];
		}
		return $slider_group;

	}

	private function get_gallery_id($url)
	{
		// Create DOM from URL or file
		$html = file_get_html($url);
		$scripts = $html->find('div.photoGalleryElement');

		$data = '';
	    foreach($scripts as $s) {
	        if(strpos($s->innertext, '<div class="thumbnail') !== false) {
	        	$data .= $s->innertext;
	        }
	    }

        /* Get only the images */
        $rel = '/rel="lightbox(.*?)"/m';
        if(preg_match_all($rel, $data)) {
    	    preg_match_all($rel, $data, $matches, PREG_SET_ORDER, 0);
        }

        $image_gallery = array();
        foreach($matches as $arr_images){
        	foreach($arr_images as $image_id){
        		$image_id = str_replace('lightbox','',$image_id);
        		$image_id = str_replace('[','',$image_id);
        		$image_id = str_replace('rel=','',$image_id);
        		$image_id = str_replace('"','',$image_id);
        		$image_gallery_ids[] = str_replace(']','',$image_id);
        	}
        }

	    return $image_gallery_ids;
	}

	private function get_gallery_images_v2_test($id, $url)
	{
		// Create DOM from URL or file
		$html = file_get_html($url);
		$scripts = $html->find('div.photoGalleryElement');

		/* Process the script */
		$data = '';
	    foreach($scripts as $s) {
	        if(strpos($s->innertext, 'rel="lightbox['.$id.']"') !== false) {
	        	$data .= $s->innertext;
	        }
	    }

	    /* Get only the images */
	    $re = '/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i';
	    if(preg_match_all($re, $data)) {
		    preg_match_all($re, $data, $matches, PREG_SET_ORDER, 0);
	    }

	    /* Showing all data */
		 echo "<textarea style='width:500px;height:500px;'>".$matches."</textarea>";

	    preg_match_all( '@src="([^"]+)"@' , str_replace('&amp;','&', $image_gallery), $match );
	    $image_gallery = array_pop($match);

	    return $image_gallery;
	}

	private function get_gallery_images_v2($id, $url)
	{
		// Create DOM from URL or file
		$html = file_get_html($url);
		$scripts = $html->find('div.photoGalleryElement');

		/* Process the script */
		$data = '';
	    foreach($scripts as $s) {
	        if(strpos($s->innertext, 'rel="lightbox['.$id.']"') !== false) {
	        	$data .= $s->innertext;
	        }
	    }

	    /* Get only the images */
	    $re = '/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i';
	    if(preg_match_all($re, $data)) {
		    preg_match_all($re, $data, $matches, PREG_SET_ORDER, 0);
	    }

	     $image_gallery = '';
	     foreach($matches as $arr_images){
	     	foreach($arr_images as $image){
     			$image_gallery .= strip_tags($image,'<img>');
	     	}
	     }
	     
	    /* Showing all data */
		 // echo "<textarea style='width:500px;height:500px;'>".$data."</textarea>";

	    preg_match_all( '@src="([^"]+)"@' , str_replace('&amp;','&', $image_gallery), $match );
	    $image_gallery = array_pop($match);

	    // $image_gal = '';
	    // foreach($image_gallery as $image) {
	    // 	$image_gal .= $url . $this->get_actual_img_url_v2($image);
	    // }

	    // return str_replace('imagesWebsites', 'Websites', $image_gal);

	    return $image_gallery;
	    // $images = '';
	    // foreach($image_gallery as $image){
	    // 	$images .= $image.'<br/>';
	    // }
	    // return $images;

	}

	private function get_gallery_images($url)
	{
		// Create DOM from URL or file
		$html = file_get_html($url);
		$scripts = $html->find('div.photoGalleryElement');

		/* Process the script */
		$data = '';
	    foreach($scripts as $s) {
	        if(strpos($s->innertext, '<img src="') !== false) {
	        	$data .= $s->innertext;
	        }
	    }

	    /* Get only the images */
	    $re = '/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i';
	    if(preg_match_all($re, $data)) {
		    preg_match_all($re, $data, $matches, PREG_SET_ORDER, 0);
	    }

	     $image_gallery = '';
	     foreach($matches as $arr_images){
	     	foreach($arr_images as $image){
	     		
	     		// if($image !== '"&"'){
	     		// 	$image = str_replace('"&img=%2f','',$image);
	     		// 	$image = str_replace('%2f','/',$image);
	     		// 	$image = str_replace('+',' ',$image);
			     // 	$image = strtok($image, '&');
	     		// }
     			$image_gallery .= strip_tags($image,'<img>');
	     	}
	     }
	     
	    /* Showing all data */
		 // echo "<textarea style='width:500px;height:500px;'>".$data."</textarea>";

	    preg_match_all( '@src="([^"]+)"@' , str_replace('&amp;','&', $image_gallery), $match );
	    $image_gallery = array_pop($match);

	    // $image_gal = '';
	    // foreach($image_gallery as $image) {
	    // 	$image_gal .= $url . $this->get_actual_img_url_v2($image);
	    // }

	    // return str_replace('imagesWebsites', 'Websites', $image_gal);

	    return $image_gallery;
	}

	private function get_slider_images_debug($url)
	{
		// Create DOM from URL or file
		$html = file_get_html($url);
		$scripts = $html->find('script');

		/* Process the script */
		$data = array();
	    foreach($scripts as $s) {
	        if(strpos($s->innertext, '&img=%2f') !== false) {
	        	$s = str_replace(' ','',$s->innertext);
    			// $data .= $s;
    			$data[] = $s;
	        }
	    }

	    print_r($data);
	}

	private function divi_reupload_img_slider($url, $img_arr)
	{
		// Last filter

			// Process the reuploading
			$url = $this->get_base_url($url);
			$data = $img_arr;

			$divi_slider_gallery = '[et_pb_slider _builder_version="4.0.6" hover_enabled="0"]';
			foreach($data as $image) {

				$image_url = $url . $image;
				$image_url = htmlentities(str_replace(' ','%20', $image_url));

				$upload_dir = wp_upload_dir();
				$image_data = @file_get_contents( $image_url );

				if ( $image_data === false )
				{
				   
				}

				$image_name = basename( str_replace('-',' ',$image_url) );
				$filename = basename( $this->divi_clean_filename($image_url) );

				if ( wp_mkdir_p( $upload_dir['path'] ) ) {
				  $file = $upload_dir['path'] . '/' . $filename;
				} else {
				  $file = $upload_dir['basedir'] . '/' . $filename;
				}
				file_put_contents( $file, $image_data );
				$wp_filetype = wp_check_filetype( $filename, null );
				$attachment = array(
				  'post_mime_type' => $wp_filetype['type'],
				  'post_title' => sanitize_file_name( $filename ),
				  'post_content' => '',
				  'post_status' => 'inherit'
				);

				// If exist update, if not then add
				if (post_exists($filename)){
			        $page = get_page_by_title($filename, OBJECT, 'attachment');
			        $attach_id = $page->ID;
			        $reupload_image_url = wp_get_attachment_url( $attach_id );
				} else {
					$attach_id = wp_insert_attachment( $attachment, $file );
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
					wp_update_attachment_metadata( $attach_id, $attach_data );
					$reupload_image_url = wp_get_attachment_url( $attach_id );
				}

				$divi_slider_gallery .= '[et_pb_slide heading="" button_text="" _builder_version="4.0.6" background_enable_color="off" background_image="'.$reupload_image_url.'" background_enable_image="on" hover_enabled="0"][/et_pb_slide]';

			}
			$divi_slider_gallery .= '[/et_pb_slider]';

			// print_r($divi_slider_gallery);
			return $divi_slider_gallery;
		

	}

	private function divi_clean_filename($image_url) {
		$filename = basename( str_replace(' ','-', $image_url) );
		$filename = basename( str_replace('%2B','-',$filename) );
		$filename = basename( str_replace('%20','-',$filename) );
		$filename = basename( str_replace('_','',$filename) );
		$filename = basename( preg_replace('/[^A-Za-z0-9\.]/','',$filename) );
		$filename = basename( strtolower($filename) );
		return $filename;
	}

	private function divi_reupload_img_gallery($url, $data, $slider, $debug)
	{
		$url = $this->get_base_url($url);
		if($slider == 1) {
			$image_urls = array();
		} else {
			$image_ids = '';
		}
		if($debug == 1) {

			/*Debug Mode*/
			$divi_slider_gallery  = '[et_pb_row _builder_version="4.0.6"]';
			$divi_slider_gallery .= '[et_pb_column _builder_version="4.0.6" type="4_4"]';

			/* Gallery */
			$divi_slider_gallery .= '[et_pb_gallery _builder_version="4.0.6" gallery_ids="NO IDS AVAILABLE FOR DEBUG" hover_enabled="0"]';
			$divi_slider_gallery .= '[/et_pb_gallery]';

			/* Closing Sections and Rows */
			$divi_slider_gallery .= '[/et_pb_column]';
			$divi_slider_gallery .= '[/et_pb_row]';

			return $divi_slider_gallery;

		} else {
			/* Production */
			foreach($data as $image) {

				if($slider == 1){
					$image_url = $url . $image;
					$image_url = htmlentities(str_replace(' ','%20', $image_url));
				} else {
					$image_url = $url . str_replace('imagesWebsites','Websites',$this->get_actual_img_url_v2($image));
				}

				$upload_dir = wp_upload_dir();
				$image_data = file_get_contents( $image_url );
				$image_name = basename( str_replace('-',' ',$image_url) );
				$filename = basename( $this->divi_clean_filename($image_url) );

				if ( wp_mkdir_p( $upload_dir['path'] ) ) {
				  $file = $upload_dir['path'] . '/' . $filename;
				} else {
				  $file = $upload_dir['basedir'] . '/' . $filename;
				}
				file_put_contents( $file, $image_data );
				$wp_filetype = wp_check_filetype( $filename, null );
				$attachment = array(
				  'post_mime_type' => $wp_filetype['type'],
				  'post_title' => sanitize_file_name( $filename ),
				  'post_content' => '',
				  'post_status' => 'inherit'
				);
				// If exist update, if not then add
				if (post_exists($filename)){
			        $page = get_page_by_title($filename, OBJECT, 'attachment');
			        $attach_id = $page->ID;
			        $reupload_image_url = wp_get_attachment_url( $attach_id );
				} else {
					$attach_id = wp_insert_attachment( $attachment, $file );
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
					wp_update_attachment_metadata( $attach_id, $attach_data );
					$reupload_image_url = wp_get_attachment_url( $attach_id );
				}

				if($slider == 1) {
					$image_urls[] = array('img_title' => $image_name, 'img_url' => $reupload_image_url);
				} else {
					$image_ids .= $attach_id . ",";
				}
			}

			/* Insert Sections and Rows */
			// $divi_slider_gallery = '[et_pb_row _builder_version="4.0.6"]';
			// $divi_slider_gallery .= '[et_pb_column _builder_version="4.0.6" type="4_4"]';

			if($slider == 1){

				/*Slider*/
				$divi_slider_gallery .= '[et_pb_slider _builder_version="4.0.6" hover_enabled="0"]';

				foreach($image_urls as $image_slider) {
					/* If we support the heading */
					// $divi_slider_gallery .= '[et_pb_slide heading="'.strtoupper(strtok($image_slider['img_title'],'.')).'" button_text="" _builder_version="4.0.6" background_enable_color="off" background_image="'.$image_slider['img_url'].'" background_enable_image="on" hover_enabled="0"]';

					$divi_slider_gallery .= '[et_pb_slide heading="" button_text="" _builder_version="4.0.6" background_enable_color="off" background_image="'.$image_slider['img_url'].'" background_enable_image="on" hover_enabled="0"]';

					/*If we support the description though*/
					// $divi_slider_gallery .= '<p>Your content goes here. Edit or remove this text inline or in the module Content settings. You can also style every aspect of this content in the module Design settings and even apply custom CSS to this text in the module Advanced settings.</p>';

					$divi_slider_gallery .= '[/et_pb_slide]';
				}
				$divi_slider_gallery .= '[/et_pb_slider]';

			} else {

				/* Gallery */
				$image_ids = substr($image_ids,0,-1);

				$divi_slider_gallery .= '[et_pb_gallery _builder_version="4.0.6" gallery_ids="'.$image_ids.'" hover_enabled="0"]';
				// $divi_slider_gallery .= '[et_pb_gallery _builder_version="4.0.6" gallery_ids="'.$image_ids.'" fullwidth="on" hover_enabled="0"][/et_pb_gallery]';

				$divi_slider_gallery .= '[/et_pb_gallery]';
			}

			/* Closing Sections and Rows */
			// $divi_slider_gallery .= '[/et_pb_column]';
			// $divi_slider_gallery .= '[/et_pb_row]';

			return $divi_slider_gallery;
		}

	}

	private function divi_reupload_img($url){
		$image_url = str_replace('/../../../../../../','/',$url);
		$upload_dir = wp_upload_dir();
		$image_data = file_get_contents( $image_url );
		$filename = basename( $this->divi_clean_filename($image_url) );

		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
		  $file = $upload_dir['path'] . '/' . $filename;
		} else {
		  $file = $upload_dir['basedir'] . '/' . $filename;
		}
		file_put_contents( $file, $image_data );
		$wp_filetype = wp_check_filetype( $filename, null );
		$attachment = array(
		  'post_mime_type' => $wp_filetype['type'],
		  'post_title' => sanitize_file_name( $filename ),
		  'post_content' => '',
		  'post_status' => 'inherit'
		);

		// If exist update, if not then add
		if (post_exists($filename)){
	        $page = get_page_by_title($filename, OBJECT, 'attachment');
	        $attach_id = $page->ID;
	        $reupload_image_url = wp_get_attachment_url( $attach_id );
		} else {
			$attach_id = wp_insert_attachment( $attachment, $file );
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			$reupload_image_url = wp_get_attachment_url( $attach_id );
		}

		return $reupload_image_url;
	}

	/**
	*  Process the data to convert into divi
	*  Inserting Section > Row > $data
	*  $data are being converted into divi shortcodes in divi_convert_element()
	*/
	private function divi_section($element, $data)
	{
		$divi_converted = array();
		$divi_converted[]  = '[et_pb_section fb_built="1" admin_label="section" _builder_version="3.22"]';
		$divi_converted[]  = '[et_pb_row _builder_version="4.0.6"]';
		$divi_converted[]  = '[et_pb_column _builder_version="4.0.6" type="4_4"]';
		$count = 0;
		foreach($data as $sequence) {
			$count++;
			/*Skip the element identifier*/
			if($count !== 1) {
				if(!empty($sequence) || $sequence !== ''){
					$divi_converted[] = $this->divi_convert_element($element,$sequence);
				}
			}
		}
		$divi_converted[] = '[/et_pb_column]';
		$divi_converted[] = '[/et_pb_row]';
		$divi_converted[] = '[/et_pb_section]';
		return $divi_converted;
	}

	/**
	*  Converting dom elements to a divi shortcode data to complete the data from divi_convert_to_divi()
	*/
	private function divi_convert_element($element,$data)
	{
			$data = trim(preg_replace('/\s\s+/', ' ', $data));
			switch($element) {
				case "p":
				case "h1":
				case "h2":
				case "h3":
				case "h4":
				case "h5":
				case "h6":
					$html = '[et_pb_text _builder_version="4.0.6"]<'.$element.'>'.$data.'</'.$element.'>[/et_pb_text]';
					break;
				case "a":
					$html = '[et_pb_text _builder_version="4.0.6"]'.$data.'[/et_pb_text]';
					break;
				case "img":
					$html = '[et_pb_image _builder_version="4.0.6" src="'.$data.'" hover_enabled="0"][/et_pb_image]';
					break;
				default:
			}
			return($html);
	}

	private function divi_get_all_pages()
	{
	    $pages = get_pages(); 
		?>
		<small class="form-text text-muted" style="margin-bottom:5px;">Only <strong>published</strong> pages are visible</small>
		<?php
		global $post;
		$args = array(
			'show_option_none' => '- Select Parent Page - ',
		    'child_of' => $post->ID
		);
		wp_dropdown_pages( $args );
	}

	private function divi_get_publish_status()
	{
	 	$statuses = array('publish','draft');
		?>
		<select name="status" class="status-style"> 
		    <option value="0"><?php echo esc_attr( __( '- Select Status -' ) ); ?></option> 
		    <?php  foreach($statuses as $status): ?>
				<option value="<?php echo $status; ?>" <?php echo ($status == 'publish' ? 'selected' : ''); ?> style="text-transform:capitalize;"><?php echo $status; ?></option>
		    <?php endforeach; ?>
		</select>
		<?php
	}

	/**
	*  Insert the post after filtering the data
	*/
	private function divi_insert_post($parent_id, $status, $title, $content, $url_slug)
	{
		$user = wp_get_current_user();
			$my_post = array(
			  'post_type'   	=> 'page',
			  'post_title'    	=> $title,
			  'post_content'  	=> $content,
			  'post_status'   	=> $status,
			  'post_author'   	=> $user->ID,
			  'post_parent'	  	=> $parent_id,
			  'post_name' 		=> $url_slug,
			  'meta_input' => array(
					'_et_pb_use_builder' => 'on',
				)
			);

		// Insert the post into the database
		$postID = wp_insert_post( $my_post );
		return $postID;
	}

	private function divi_redirect_url($url, $new_url){
		global $wpdb;

		$wp_redirect_tb = $wpdb->prefix.'redirection_items';
		$check_exist = $wpdb->get_var("SELECT COUNT(*) FROM $wp_redirect_tb WHERE url = '$url'");
		if(!$check_exist) {
			$wpdb->insert($wp_redirect_tb, array(
			    'url' 			=> $url,
			    'match_url' 	=> $url,
			    'match_data' 	=> NULL,
			    'regex' 		=> 0,
			    'position' 		=> 0,
			    'last_access'	=> '0000-00-00 00:00:00',
			    'group_id' 		=> 1,
			    'status' 		=> 'enabled',
			    'action_type'	=> 'url',
			    'action_code'	=> '301',
			    'action_data' 	=> $new_url,
			    'match_type' 	=> 'url',
			    'title' 		=> '',
			));
		}

	}

	private function multiexplode ($delimiters,$string) {
	    $ready = str_replace($delimiters, $delimiters[0], $string);
	    $launch = explode($delimiters[0], $ready);
	    return  $launch;
	}

	/* DEBUG */
	private function divi_crawl_url_debug($url,$row_module,$debug)
	{
		// Start :: Process Curl
	    $url = filter_var($url, FILTER_SANITIZE_URL);
	    $ch = curl_init();
	    $timeout = 5;
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	    $html = curl_exec($ch);
	    curl_close($ch);

	    $html = preg_replace('/<div id="subCol">(.*)<\/div>/s', '', $html);
	    // Process Title  -> Problem, so create a different function that could load the titles before entering foreach
	    $titleraw 	= preg_match("/\<title.*\>(.*)\<\/title\>/isU", $html, $matches);
        $titleraw	= explode('/', $matches[1]);
        $title 		= $titleraw[0]; // Title

		// Replace Comments to Sections
	    $html = preg_replace('/<!-- Start_Content_(\d+) -->/i', '<section class="lcms_content_block" id="${1}">', $html);
	    $html = preg_replace('/<!-- End_Content_(\d+) -->/i', '</section><!-- end ID ${1} -->', $html);

	    // Replace Headers outside to textElements
	    $html = preg_replace('/<div id="main_1_pnlTitle" class="container">(.*)<\/div>/s', '<div class="textElement">$1</div>', $html);
	    $html = preg_replace('/<div id="sidebar_4_pnlTitle" class="container">(.*)<\/div>/s', '<div class="textElement">$1</div>', $html);
		

	    // Load DOM
	    $dom = new DOMDocument();
	    @$dom->loadHTML($html);
	    libxml_use_internal_errors (true);

		// Get all contents under 'section'
	    $complete_content = $dom->getElementsByTagName('section');

	    // Value Preparations
	    $content = "";
        $count = 0;
    	$content_gallery_fid = array();

	    // DOM Slicing
		$match_id = '';
		$i = 0;
	    foreach($complete_content as $section) {

	    	// Javascript removal
			while (($remove_script = $dom->getElementsByTagName("script")) && $remove_script->length) {
	            $remove_script->item(0)->parentNode->removeChild($remove_script->item(0));
		    }

		    // Default :: Divi Row Opening (for 1 whole row only)
		    if($row_module == 1) {
		    	$content .= '[et_pb_row _builder_version="4.0.6"][et_pb_column _builder_version="4.0.6" type="4_4"]';
		    }

		    // DOM Content Slicing
			if(strpos($dom->saveXML($section), '"textElement"')) {
				$content .= $this->divi_section_v2($url, 'textElement', preg_replace("/&#?[a-z0-9]{2,8};/i", '', $dom->saveXML($section)));
	    	}

			if(strpos($dom->saveXML($section), '"linksModule"')) {
				$content .= $this->divi_section_v2($url, 'textElement', preg_replace("/&#?[a-z0-9]{2,8};/i", '', $dom->saveXML($section)));
	    	}

			// Default :: Divi Row Closing (for 1 whole row only)
	    	if($row_module == 1) {
	    		$content .= '[/et_pb_column][/et_pb_row]';
	    	}

		}
    	print_r("<textarea cols='200' rows='100'>".$content."</textarea>");
	}

}
new CAVEIMDEV_DIVI_CRAWLER();

/* Run extra functions in here */

// function remove_dropdown_spaces($output){
//     $output = str_replace('&nbsp;', '-', $output);
//     return $output;
// }
// add_filter( 'wp_dropdown_pages', 'remove_dropdown_spaces' );