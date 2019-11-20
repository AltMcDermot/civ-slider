<?php

/**
 *
	Main function to diplay on front end
 */
function create_civ_db() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'civ_slider';

	$sql = "create table $table_name(
  id INT NOT NULL AUTO_INCREMENT, 
  PRIMARY KEY(id),
  post_id int,
  img_order INT)";

	$wpdb->query(
		'create table $table_name(
	  id INT NOT NULL AUTO_INCREMENT, 
	  PRIMARY KEY(id),
	  post_id int,
	  img_order INT)' 
	);
}

add_action( 'admin_init', 'create_civ_db' );

add_action( 'add_meta_boxes', 'civ_meta_box' );

/** 
	Add the meta box to post/page pages
 */
function civ_meta_box() {

	add_meta_box( 'my box', 'Civ Slider','show_my_meta_box', 'post' );

	function show_my_meta_box( $post ) {
		//	This will count the amount of slides total to insert for the slide order .
		
		global $wpdb;
		$table_name  = $wpdb->prefix . 'civ_slider';
		$row_count   = $wpdb->get_var( 'select count(*) from $table_name' );
		$slide_count = $row_count + 1;
		$post_id_check = $post->ID;

		$box_check = $wpdb->get_row($wpdb->prepare("select * from $table_name where post_id=%d", $post_id_check));
?>
	<form method="post" >
		<label>Check here to add post to homepage slider: </label>
		<input type="checkbox" name="chkd" <?php  if($box_check != null ){echo "checked";} ?> />
		<input type="hidden" value="<?php echo $post->ID; ?>" name="post_id" />
		<input type="hidden" value="<?php echo $slide_count; ?>" name="slide_order" />
	</form>
		<?php
	}
}

/**
When the post is saved, this will add the slide to the featured list.
 */
function civ_add_slide( $post ) {

  global $wpdb;
  $table_name = $wpdb->prefix.'civ_slider';
  $civ_post_id = $_POST['post_id'];
  $civ_slide_order = $_POST['slide_order'];
  $civ_checkbox = $_POST['chkd'];
  
  // Here we need to make sure that that checkbox is checked to add 
  // a new entry to the table. Also we have to make sure there are
  // no duplicates

  $chk_dupes = $wpdb->get_row($wpdb->prepare("select * from $table_name where post_id=%d", $civ_post_id));

  if($civ_checkbox == "on"){
    if($chk_dupes == null){
      $wpdb->insert($table_name, array(
      'post_id' => $civ_post_id,
      'img_order' => $civ_slide_order));
    }
  } 
  else{
    if ($chk_dupes != null){
      $wpdb->delete($table_name, array('post_id' =>$civ_post_id));
    }
  }
}
add_action('save_post', 'civ_add_slide');

function civ_slider(){
  // Here we will gather the post ids from the custom table
  // to use in the WP_Query loop
  global $wpdb;
  $table_name = $wpdb->prefix . 'civ_slider';
  $slides = $wpdb->get_results( "select * from $table_name order by img_order asc", 'object');

  $f_slide = array();
  $num_slides = count($slides);

  foreach ($slides as $slide) {
    array_push($f_slide, $slide->post_id);
  }
$args = array('post_type' => 'post',
              'post__in' => $f_slide,
              'orderby' => 'post__in',
              'ignore_sticky_posts' => '1'
);

 $slider = new WP_Query($args);


  while ( $slider->have_posts() ) :
    $slider->the_post(); ?> 
<?php 
    //Get the Thumbnail URL
    $src = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), /*array( 720,405 ),*/ false, '' );
    ?>
      <div class="slide" style="background-image:url('<?php echo $src[0]; ?>');">
        <h2>
          <a href="<?php the_permalink(); ?>">
            <?php the_title(); ?>
          </a>
        </h2>
        <span><?php the_excerpt(); ?></span>
      </div>
     <?php endwhile;
    $wpdb->flush();
wp_reset_postdata();
} 

//build form to reorder posts

function civ_slider_options_page(){
  add_options_page('Civ Slider Options', 'Civ Slider Options', 8, 'civ_slider', 'civ_slider_options');
}
add_action('admin_menu', 'civ_slider_options_page');

function civ_slider_options(){
?>
<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css" />
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
<script type="text/javascript">
  jQuery(function(){
    jQuery('#sort').sortable();
    jQuery('#sort').disableSelection();
  });
  </script>
<?php
  global $wpdb;
  $table_name = $wpdb->prefix . 'civ_slider';

  // Delete Posts

  if((isset($_POST['order']) && (isset($_POST['chkd'])))){
    $x = 1;
    $check_delete = $_POST['chkd'];
    foreach ($_POST['order'] as $index => $order) {
      if ($check_delete[$index]  == "on") {
        # code...
      $wpdb->query($wpdb->prepare("delete from $table_name where post_id= %d", $order));
      ++$x;
      }
    }
  } 



  //changing post order on submit
  if(isset($_POST['order'])){
    $x = 1;
    foreach ($_POST['order'] as $order) {
      $reorder = "";
      $wpdb->query($wpdb->prepare("update $table_name set img_order= %d where post_id= %d", $x, $order));
      ++$x;
    }
  } 


  //displaying posts for reordering
  $slides = $wpdb->get_results( "select * from $table_name order by img_order asc", 'object');
  $f_slide = array();
  $num_slides = count($slides);


  foreach ($slides as $slide) {
    array_push($f_slide, $slide->post_id);
  }
  $args = array('post_type' => 'post',
              'post__in' => $f_slide,
              'orderby' => 'post__in',
              'ignore_sticky_posts' => '1'
);
  $slider = new WP_Query($args);

  ?>

Change the order of the slider here.
<style type="text/css">
#sort{width:600px;border:1px #000 groove;}
#sort li{background-color: #efefef;color:#000; padding: 5px 10px;margin: 0;}
#sort li:nth-of-type(2n){background-color: #000;color:#fff;}
</style>

<form method="post">
  <ul id="sort">
<?php 
  if ($wpdb->get_results( "select * from $table_name order by img_order asc", 'object')) {
   while ( $slider->have_posts() ) :
    $slider->the_post(); ?> 
    <li><?php the_title(); ?><input name="order[]" type="hidden" value="<?php echo $f_slide[$i] ;?>" />  <input type="checkbox" name="chkd[]" /></li>
<?php 
  ++$i;
  endwhile;
  $wpdb->flush();
  wp_reset_postdata();
?>

  <?php
} else {echo "no slides";} ?>
  </ul>
  <input type="submit"/>
</form> 
<?php }


function show_slider(){
  //code to show slider in front end.
  ?>
  <section 
  id="featured" 
  class="cycle-slideshow" 
  data-cycle-slides="div"
  data-cycle-fx="fade"
  data-cycle-manual-speed="200" 
  data-pause-on-hover="true">
    <?php civ_slider(); ?>
  </section>
  <?php
}
