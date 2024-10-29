<?php
/*
Plugin Name: AnyWP Weather
Plugin URI: http://anywp.com/plugins
Description: Sidebar widget to display the weather forecast of your city.
Author: AnyWP
Version: 1.0
Author URI: http://anywp.com
*/

class AnyWP_Weather extends WP_Widget {
	
	function AnyWP_Weather() {
		$widget_ops = array('description' => __('Display weather and forecast', 'AnyWP'));
		parent::WP_Widget(false, __('AnyWP Weather', 'AnyWP'), $widget_ops);  
	}

	function form($instance) {
		$title = if_var_isset($instance['title'], '');
		$place = if_var_isset($instance['place'], '');
		$unit = if_var_isset($instance['unit'], '');
		echo '<p><label for="' . $this->get_field_id('title') . '">' . __('Title:', 'AnyWP') . '</label><br /><input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . esc_attr($title) . '" /></p>';
		echo '<p><label for="' . $this->get_field_id('place') . '">' . __('City/Place:', 'AnyWP') . '</label><br /><input class="widefat" id="' . $this->get_field_id('place') . '" name="' . $this->get_field_name('place') . '" type="text" value="' . esc_attr($place) . '" /></p>';
		echo '<p><label for="' . $this->get_field_id('unit') . '">' . __('Unit:', 'AnyWP') . '</label><br /><input type="radio" name="' . $this->get_field_name('unit') . ' id="' . $this->get_field_id('unit') . '" value="Fahrenheit"' . ($unit != 'Celsius' ? ' checked' : '') . ' /> <label>Fahrenheit</label> &nbsp; <input type="radio" name="' . $this->get_field_name('unit') . ' id="' . $this->get_field_id('unit') . '" value="Celsius"' . ($unit == 'Celsius' ? ' checked' : '') . ' /> <label>Celsius</label></p>';
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		$instance['place'] = $new_instance['place'];
		$instance['unit'] = $new_instance['unit'];
		return $instance;
	}

	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		echo $before_widget;
		$title = if_var_isset($instance['title'], '');
		if ($title != '') echo $before_title . $title . $after_title;
		$request = 'http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20geo.places%20where%20text%3D%22' . urlencode(if_var_isset($instance['place'], '')) . '%22&format=xml';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $request);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$xml = curl_exec($ch);
		curl_close($ch);
		if (strpos($xml, 'xml ')) {
			$xml = simplexml_load_string($xml);
			$woeid = $xml->results->place->woeid;
			if ($woeid != '') {
				$unit = '';
				if (if_var_isset($instance['unit'], '') == 'Celsius') $unit = '&u=c';
				$request = 'http://weather.yahooapis.com/forecastrss?w=' . $woeid . $unit;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $request);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$xml = curl_exec($ch);
				curl_close($ch); 
				if (strpos($xml, '[CDATA[')) {
					$xml = substr($xml, strpos($xml, '[CDATA[') + 7);
					$xml = substr($xml, 0, strpos($xml, ']'));
					$xml = str_ireplace('<BR/>', '<BR />', $xml);
					$xml = str_ireplace('<BR />\n<BR />', '<BR /><BR />', $xml);
					$xml = str_ireplace('<BR /><BR />', '<BR />', $xml);
					$xml = str_ireplace("<BR /><b>Forecast:</b>", '<div class="weather-text"><b>Forecast:</b>', $xml);
					$xml = str_ireplace(')<br />', ')</div>', $xml);
					$xml = str_ireplace("<br />\n<a href", '</div><div class="weather-links"><a href', $xml);
					$xml = str_ireplace('<BR />', '<br />', $xml);
					if (strpos($xml, 'alt=') === false && strpos($xml, '<img ')) $xml = str_ireplace('<img ', '<img alt="" ', $xml);
				}
			}
			echo $xml;
		}
		echo $after_widget;
	}
}

if (! function_exists('if_var_isset')) {
	function if_var_isset(&$check, $or = null) {
		return (isset($check) ? $check : $or);
	}
}
	
function anywp_weather_widget() {
	register_widget('AnyWP_Weather');
}

add_action('widgets_init', 'anywp_weather_widget');

?>
