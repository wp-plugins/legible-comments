<?php
/*
 * Plugin Name: Legible Comments for Wordpress
 * Version: 0.5.5
 * Plugin URI: http://www.sortea2.com/blog/2010/03/plugin-legible-comments-for-wordpress-2/
 * Description: Transforms comments to be readable: Capitalization and well-spaced words.
 * Author: Alejandro Bernabé
 * Author URI: http://www.trackingo.com/
 *
    Copyright 2010  Alejandro Bernabé  (email : alex@sortea2.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function of_muestra_info() {
	if (get_option('linkLegibleComments') == 'on') {
		$texto = "<label><a href='http://www.sortea2.com/blog/2010/03/plugin-legible-comments-for-wordpress-2/'>Legible Comments</a> modifier is on</label>";
	} else {
		$texto = '';
	}
	
	echo $texto;
}
add_action('comment_form', 'of_muestra_info');

function of_cambia_content($content) {

	include_once("CleanText.class.php");

	if (get_option('enableEmCLeg') == 'on') {
		$em = true;
	} else {
		$em = false;
	}

	$clean = new CleanText($content,$em);
	return $clean->of_clean_text();
}
add_filter('pre_comment_content', 'of_cambia_content');

function of_activa_plugin() {
	add_option('linkLegibleComments', 'off');
	add_option('enableEmCLeg', 'on');
}
register_activation_hook( __FILE__, 'of_activa_plugin');


function of_add_options_page() {
	global $wpdb;
	add_options_page('Legible Comments Options', 'Legible Comments', 8, basename(__FILE__), 'of_options_page');
}
add_action('admin_menu', 'of_add_options_page');

function of_options_page() {

	if (isset($_POST['info_update'])) {
		$linkLegibleComments = $_POST['enableLinkLeg'];
		$emLegibleComments = $_POST['enableEmCLeg'];

		//Update the options
		update_option('linkLegibleComments', $linkLegibleComments);
		update_option('enableEmCLeg', $emLegibleComments);

		echo "<div class='updated'><p><strong>Options uptaded</strong></p></div>";
	}

	if (get_option('linkLegibleComments') == 'on') {
		$checked = "checked='checked'";
	} else {
		$checked = "";
	}
	
	if (get_option('enableEmCLeg') == 'on') {
		$checkedEm = "checked='checked'";
	} else {
		$checkedEm = '';
	}

	echo "<form method='post' action='options-general.php?page=legible-comments.php'>
		<div class='wrap'>
		<h2>Legible Comments Options</h2>
		<p><input type='checkbox' name='enableLinkLeg' ".$checked." id='enableLinkLeg'/> <label for='enableLinkLeg'>Enable link in the comment box</label>.</p>
		<p><input type='checkbox' name='enableEmCLeg' ".$checkedEm." id='enableEmCLeg'/> <label for='enableEmCLeg'>Put in italics the text between quotes.</label></p>
		<p class='submit'><input type='submit' name='info_update' value='Update Options' /></p>
		</div></form>";

}

?>