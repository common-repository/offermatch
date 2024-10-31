<?php
/*
Plugin Name: OfferMatch Widget
Plugin URI: http://godropship.com/OfferMatch.html
Description: Create recommended offers and tag them. The widget will display them on posts which match the tags.
Author: GoDropShip.com
Version: 1.0
Author URI: http://godropship.com
*/ 

/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once(ABSPATH . 'wp-includes/pluggable.php');
$wpdb->show_errors=true;
include('offermatch-html.php');

function offermatch_menu() {  
  # add_menu_page('OfferMatch', 'OfferMatch', 7, __FILE__, 'offermatch_offers');
  add_submenu_page('plugins.php', 'OfferMatch', 'OfferMatch', 7, __FILE__, 'offermatch_offers');
}

/* Creates the mysql tables needed to store mailing list and messages */
$offermatch_db_version="1.0";
$offers_table= $wpdb->prefix. "offermatch_offers";
function offermatch_install()
{
    global $wpdb, $offermatch_db_version;
    $offers_table= $wpdb->prefix. "offermatch_offers";
    
    if($wpdb->get_var("SHOW TABLES LIKE '$offers_table'") != $offers_table) {
	  		
			$sql = "CREATE TABLE " . $offers_table . " (
				  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  title VARCHAR(255) NOT NULL UNIQUE,	
				  offer TEXT NOT NULL,		  
                  tags TEXT NOT NULL,
				  status TINYINT UNSIGNED NOT NULL				  
				);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');				
			dbDelta($sql);
	}
    add_option("offermatch_db_version", $offermatch_db_version);
}


// the function that lets you add/edit/delete offers
function offermatch_offers()
{
    global $wpdb, $offers_table;
    if(isset($_POST['title'])) $title=$wpdb->escape($_POST['title']);
    if(isset($_POST['offer'])) $offer=$wpdb->escape($_POST['offer']);
    if(isset($_POST['tags'])) $tags=$wpdb->escape($_POST['tags']);
    if(isset($_POST['status'])) $status=$wpdb->escape($_POST['status']);
    
    if(!empty($_POST['add_offer']))
    {
        $sql="INSERT INTO $offers_table (title,offer,tags,status)
        VALUES (\"$title\",\"$offer\",\"$tags\",\"$status\")";
        $wpdb->query($sql);
    }
    
    if(!empty($_POST['save_offer']))
    {
        $sql="UPDATE $offers_table 
        SET title=\"$title\", offer=\"$offer\", 
        tags=\"$tags\", status=\"$status\"
        WHERE id='$_POST[id]'";
        $wpdb->query($sql);
    }
    
    if(!empty($_POST['delete_offer']))
    {
        $sql="DELETE FROM $offers_table WHERE id='$_POST[id]'";
        $wpdb->query($sql);
    }
    
    // select offers
    $sql="SELECT * FROM $offers_table ORDER BY title";
    $offers=$wpdb->get_results($sql);
    
    offermatch_display_offer_form($offers);
}

// the widget object
class OfferMatch extends WP_Widget {
    /** constructor */
    function OfferMatch() {
        parent::WP_Widget(false, $name = 'OfferMatch');
    }
 
    // ovpredct_options() displays the page content for the Ovpredct Options submenu
    function form() 
    {
        // Read in existing option values from database
        $num_offers = stripslashes( get_option( 'offermatch_num_offers' ) );
        $default_offer= stripslashes( get_option( 'offermatch_default_offer' ) );        
        
        // See if the user has posted us some information
        // If they did, this hidden field will be set to 'Y'
        if( $_POST[ 'offermatch_update' ] == 'Y' ) 
        {
            // Read their posted values
            $num_offers = (is_numeric($_POST[ 'offermatch_num_offers' ]) 
                and $_POST[ 'offermatch_num_offers' ]>0)?$_POST[ 'offermatch_num_offers' ]:1;
            $default_offer = $_POST[ 'offermatch_default_offer' ];                          
            
            // Save the posted values in the database
            update_option( 'offermatch_num_offers', $num_offers );
            update_option( 'offermatch_default_offer', $default_offer );            
            
            // Put an options updated message on the screen
    		?>
    		<div class="updated"><p><strong><?php _e('Options saved.', 'offermatch_domain' ); ?></strong></p></div>
    		<?php		
    	 }
    		
    		 // Now display the options editing screen
    		    echo '<div class="wrap">';		
    		    // header
    		    echo "<h2>" . __( 'OfferMatch Options', 'offermatch_domain' ) . "</h2>";		
    		    // options form		    
    		    ?>    		
    		
    		<input type="hidden" name="offermatch_update" value="Y">
    		
    		<p><?php _e("Num. offers to display:", 'offermatch_domain' ); ?> 
    		<input type="text" name="offermatch_num_offers" value="<?php echo (is_numeric($num_offers) and $num_offers>0)?$num_offers:1; ?>" size="4">
    		</p><hr />
    		
    		<p><?php _e("Default Offer HTML Code", 'offermatch_domain' ); ?> 
            <textarea name="offermatch_default_offer" rows="5" cols="20"><?php echo stripslashes ($default_offer); ?></textarea>
            </p><hr />    		
    		
    		</div>
    		<?php
    }
    
    // This just echoes the text
    function widget($args, $instance) 
    {	
        global $wpdb, $offermatch_db_version, $offers_table;
    	$tags=get_the_tags();
        
        // get all offers and assign them score depending on the number 
        // of tags they have present. At the end sort the offers by this score
        // and get the number of offers which we'll display
        // in case there's not even one offer, display the default one
        $sql="SELECT * FROM $offers_table WHERE status=1 ORDER BY title";
        $offers=$wpdb->get_results($sql);  
        
        $offers_found=false;
        foreach($offers as $cnt=>$offer)
        {
            $offers[$cnt]->rank=0; 
            $otags=explode(",",$offer->tags);
            
            foreach($otags as $otag)
            {
                $otag=trim($otag);
                
                foreach($tags as $tag)
                {
                    if(strcmp(strtolower($tag->slug),strtolower($otag))==0)
                    {
                        $offers[$cnt]->rank++;
                        if(!$offers_found) $offers_found=true;
                    }
                }
            }
        }
                
        if(!$offers_found)
        {            
             $default_offer= stripslashes( get_option( 'offermatch_default_offer' ) );     
             echo "<div class='widget_offermatch'>";
             echo $default_offer;
             echo "</div>";
        }
        else
        {
            $num_offers = stripslashes( get_option( 'offermatch_num_offers' ) );            
            
            uasort($offers,array($this, 'sort_offers'));                   
                        
            $cnt=0;                
            foreach($offers as $offer)
            {      
                if($offer->rank==0) continue;
                if($cnt>=$num_offers) break;                
                $cnt++;
                
                echo "<div class='widget_offermatch'>";
                echo stripcslashes($offer->offer);
                echo "</div>";
            }
        }
    }    
    
    // sort offers depending on rank
    function sort_offers($offer1,$offer2)
    {
        if($offer1->rank==$offer2->rank) return 0;
        return ($offer1->rank < $offer2->rank) ? 1 : -1;
    }
}    

register_activation_hook(__FILE__,'offermatch_install');
add_action('admin_menu', 'offermatch_menu');
add_action('widgets_init', create_function('', 'return register_widget("OfferMatch");'));
?>