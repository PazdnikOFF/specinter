<?php
/**
 * This file is part of Elgrow CMS
 * Copyright 2012 Innokenty Sarayev <6319432@gmail.com>
 *
 * Elgrow CMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Elgrow CMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Тип данных для однострочных инпутов
 */
class type_gmap {
	public function input($name, $data, $comment = '') {
		$options = explode(":", $comment);
		$coords = $options[0];
		$zoom = $options[1];

		$scriptCall = '<script type="text/javascript"
			src="http://maps.google.com/maps/api/js?sensor=false">
			</script>
		';

		$script = '<script>
					  $(document).ready(function() {
						var latlng = new google.maps.LatLng('.$coords.');
						var myOptions = {
						  zoom: '.$zoom.',
						  center: latlng,
						  mapTypeId: google.maps.MapTypeId.ROADMAP
						};
						var map = new google.maps.Map(document.getElementById("map_canvas"),myOptions);

						var markersArray = [];

						google.maps.event.addListener(map, "click", function(e) {
							setMarker(e.latLng);
						  });



						 function setMarker(e) {
							 $("#coords_'.$name.'").val(e);
							 deleteOverlays();
							var marker = new google.maps.Marker({
							  position: e,
							  map: map,
							  title:""
							 });

							 markersArray.push(marker);
						}

						function deleteOverlays() {
						  if (markersArray) {
							for (i in markersArray) {
							  markersArray[i].setMap(null);
							}
							markersArray.length = 0;
						  }
						}

					  ';

					  if ($data != "") {
						  $data = str_replace("(", "", $data);
						  $data = str_replace(")", "", $data);
						  $script .= 'var latlng = new google.maps.LatLng('.$data.');
										new setMarker(latlng);';
					  }

					  $script .= '});</script>';

		$html = '<div id="map_canvas"></div><input type="hidden" name="coords_'.$name.'" id="coords_'.$name.'" value="'.$data.'"/>';
		return $scriptCall.$script.$html;
	}

	public function save($name) {
		global ${"$name"};
		if (isset($_POST['coords_'.$name])) {
			return $_POST['coords_'.$name];
		}
	}

	public function get($data, $comment, $ro) {
		return "";
	}

}
?>