<?php
/*
Plugin Name: WX-Weather Plugin
Plugin URI: http://squaredesign.com/builds/wx-weather/
Description: WX-Weather will retrieve and cache data from a weather station (via Weather Underground), and display it as a Widget. you will need to know the Weather Station code. this plugin requires PHP version 5!

Version: 0.9
Author: Michael Susz
Author URI: http://squaredesign.com
*/

/*  Copyright 2014  Michael Susz  (email : development@squaredesign.com)

    This program is free software; you can redistribute it and/or modify
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

    or download it from http://www.gnu.org/licenses/gpl.html
*/

//}
function widget_wxweather_init() {

	function widget_wxweather( $args ) {

		extract( $args );

		$wxmsg = "";
		$wxoptions = get_option( 'widget_wxweather' );

		// i want to abstract this out to a preference
		$wx_show_alt_units = true;

		if( is_array( $wxoptions ) ) {

			$wxcode = $wxoptions['code'];
			$wxtitle = $wxoptions['title'];
			$wxdata = stripslashes( $wxoptions['data'] );
			$wxupdated = $wxoptions['updated'];
			$wxunitpref = $wxoptions['unitpref'];

		}

		$wxret_url = "http://api.wunderground.com/weatherstation/WXCurrentObXML.asp?ID=" . $wxcode;

		$wxraw_xml = "";

		echo $before_widget . $before_title . $wxtitle . $after_title;

		if( "" != $wxcode ) {

			$wxtime = strtotime( $wxupdated );

			$nowtime = time();

			// this checks the difference between the saved update time and the current time
			// is less than 300 seconds (five minutes)
			if( $nowtime-$wxtime > 300 ) {

				// time expired - need to get update
				require_once( ABSPATH . WPINC . '/class-snoopy.php' );
				$wxsnoop = new Snoopy;
				$wxsnoop->agent = 'WordPress WX Weather Plugin http://squaredesign.com/builds/wx-weather/';

				// suppressing error from remote call
				@$wxresult = $wxsnoop->fetch( $wxret_url );

				if ( $wxresult ) {
					// suppress php errors
					libxml_use_internal_errors( true );
					$wxraw_xml = $wxsnoop->results;
					$wxxml = simplexml_load_string( $wxraw_xml );
					$wxerrors = libxml_get_errors();

					if( ! $wxerrors ) {
						// a response was received, set message and format results
						$wxmsg = "retrieved update from server";
						$wxoptions = get_option( 'widget_wxweather' );
						$wxoptions['data'] = addslashes( $wxraw_xml );
						$wxoptions['updated'] = "" . $wxxml->observation_time_rfc822 . "";
						update_option( 'widget_wxweather', $wxoptions );
					} else {
						// a response was received but it is not valid XML, used cached
						$wxmsg = "error processing data";
						$wxxml = simplexml_load_string( $wxdata );
					}
				} else {
					// update failed on connection - set message and use cached data
					$wxmsg = "unable to retrieve data";
					$wxxml = simplexml_load_string( $wxdata );
				}

				unset( $wxsnoop );

			} else {

				// time has not expired, using cached data
				$wxmsg = "timeout not expired - using cached data";
				$wxxml = simplexml_load_string( $wxdata );

			}

			$wxout = "";
			$wxout = '<table summary="current weather conditions for weather station ' . esc_attr( $wxxml->station_id ) . '">';
			$wxout .= '<thead><tr><th colspan="2" class="center">';
			$wxout .= $wxxml->location->full;
			$wxout .= "</th></tr></thead>\n";
			$wxout .= '<tbody class="conditions">';

			if( $wxunitpref=='metric' ) {

				$wxout .= '<tr><th scope="row">temp: </th><td>'. $wxxml->temp_c .'C';

				if( $wx_show_alt_units ) {
					$wxout .= ' (' . $wxxml->temp_f . 'F)';
				}

				$wxout .= "</td></tr>\n";

			} else {

				$wxout .= '<tr><th scope="row">temp: </th><td>' . $wxxml->temperature_string;

				if( $wx_show_alt_units ) {
					$wxout .= ' (' . $wxxml->temp_c . ')';
				}

				$wxout .= "</td></tr>\n";

			}

			$wxout .= '<tr class="humidity"><th scope="row">humidity: </th><td>' . $wxxml->relative_humidity . "%</td></tr>\n";

			// new modifications for wind given my mph/kph logic.
			// wunderground provides a string in mph - that's it

			// if wind_string says "Calm" then output Calm
			if($wxxml->wind_string=='Calm') {

				$wxout .= '<tr class="wind"><th scope="row">wind: </th><td>' . $wxxml->wind_string . "</td></tr>\n";

			} else {

				$wxwnd = ""; // temp string for wind speed
				$wxgst = ""; // temp string for gust speed

				if($wxunitpref=='metric') {

					$wxwnd = number_format( ( $wxxml->wind_mph * 1.609344 ), 2, '.', '' );
					$wxgst = number_format( ( $wxxml->wind_gust_mph * 1.609344 ), 2, '.', '' );
					$wxwndunit = 'KPH';

				} else {

					$wxwnd = $wxxml->wind_mph;
					$wxgst = $wxxml->wind_gust_mph;
					$wxwndunit = 'MPH';

				}

				$wxout .= '<tr class="wind"><th scope="row">wind: </th><td>';

				$wxout .= "From the $wxxml->wind_dir at $wxwnd $wxwndunit";

				if( $wxgst > 0 ) {
					$wxout .= " Gusting to $wxgst $wxwndunit";
				}

				$wxout .= "</td></tr>\n";

				unset($wxwnd, $wxgst); // unset temp strings

			}

			if($wxunitpref=='metric') {

				if( $wxxml->precip_1hr_metric > 0 ) {

					$wxout .= '<tr class="precip"><th scope="row">precip: </th><td>';
					$wxout .= $wxxml->precip_1hr_metric;
					$wxout .= " mm/hr</td></tr>\n";

				}

				$wxout .= '<tr class="pressure"><th scope="row">pressure: </th><td>'.$wxxml->pressure_mb . ' mb';

				if($wx_show_alt_units) {
					$wxout .=  ' (' . $wxxml->pressure_in.'")';
				}

				$wxout .= "</td></tr>\n";

			} else {

				if( $wxxml->precip_1hr_in > 0 ) {

					$wxout .= '<tr class="precip"><th scope="row">precip: </th><td>';
					$wxout .= $wxxml->precip_1hr_in;
					$wxout .= " in/hr</td></tr>\n";

				}

				$wxout .= '<tr class="pressure"><th scope="row">pressure: </th><td>';
				$wxout .= $wxxml->pressure_string;
				$wxout .= "</td></tr>\n";

			}

			$wxout .= "</tbody>\n";

			$wxout .= '<tr class="station"><th scope="row">station: </th><td>';
			$wxout .= '<a href="' . $wxxml->history_url . '">' . $wxxml->station_id . '</a>';
			$wxout .= "</td></tr>\n";

			$wxout .= '<tr class="hardware"><th scope="row">hardware: </th><td>';
			$wxout .= $wxxml->station_type;
			$wxout .= "</td></tr>\n";

			$wxout .= '<tr class="updated"><th scope="row" title="' . $wxmsg . '">updated:</th>';
			$wxout .= '<td>'.substr($wxxml->observation_time,16)."</td></tr>\n";

			if( $wxxml->ob_url != "" ) {
				$wxout .= '<tr class="forecast"><td colspan="2" class="center"><a href="'.$wxxml->ob_url.'">local forecast</a></td></tr>';
			}

			$wxout .= '</table>';

			echo $wxout;

			unset( $wxxml, $wxout, $wxmsg );

		} else {

			echo "no weather station code specified";

		}

		echo $after_widget;

	};

	function widget_wxweather_control() {

		$wxoptions = get_option( 'widget_wxweather' );

			if ( ! is_array( $wxoptions ) ) {
				$wxoptions = array( 'title'=>'', 'code'=>'' );
			}

			if ( $_POST['wxweather-submit'] ) {

				$wxoptions['title'] = strip_tags( stripslashes( $_POST['wxweather-title'] ) );
				$wxoptions['code'] = strip_tags( stripslashes( $_POST['wxweather-code'] ) );
				$wxoptions['updated'] = "0";
				$wxoptions['unitpref'] = strip_tags( stripslashes( $_POST['wxweather-unitpref'] ) );

				update_option( 'widget_wxweather', $wxoptions );

			}

			$wxtitle = htmlspecialchars( $wxoptions['title'], ENT_QUOTES );
			$wxcode = htmlspecialchars( $wxoptions['code'], ENT_QUOTES );
			$wxupdated = $wxoptions['updated'];
			$wxdata = $wxoptions['data'];
			$wxunitpref = $wxoptions['unitpref'];

			$wxunitprefmet = '';
			$wxunitprefimp = '';

			if( $wxunitpref == 'metric' ) {
				$wxunitprefmet = ' checked="checked" ';
				$wxunitprefimp = '';
			} else {
				$wxunitprefmet = '';
				$wxunitprefimp = ' checked="checked" ';
			}

			echo '<p style="text-align:right;"><label for="wxweather-title">' . __('Title:') . ' <input style="width: 200px;" id="wxweather-title" name="wxweather-title" type="text" value="'.$wxtitle.'" /></label></p>';

			echo '<p style="text-align:right;"><label for="wxweather-code">' . __('Station Code:', 'widgets') . ' <input style="width: 200px;" id="wxweather-code" name="wxweather-code" type="text" value="'.$wxcode.'" /></label></p>';

			echo '<fieldset><legend><p style="text-align:right;">Unit preference</p></legend>';

			echo '<p style="text-align:right;"><label for="unitprefimp">English (fahrenheit, inches, mph) ';

			echo '<input type="radio" id="unitprefimp" name="wxweather-unitpref" value="english" ' . $wxunitprefimp .'/></label></p>';

			echo '<p style="text-align:right;"><label for="unitprefmet">Metric (celsius, mb, kph) ';

			echo '<input type="radio" id="unitprefmet" name="wxweather-unitpref" value="metric" ' . $wxunitprefmet . ' /></label></p></fieldset>';

			echo '<input type="hidden" id="wxweather-submit" name="wxweather-submit" value="1" />';

	}

	register_sidebar_widget( array( 'WX Weather Widget', 'widgets' ), 'widget_wxweather' );

	register_widget_control( array( 'WX Weather Widget', 'widgets' ), 'widget_wxweather_control', 300, 100 );

}

add_action( 'init', widget_wxweather_init );

?>