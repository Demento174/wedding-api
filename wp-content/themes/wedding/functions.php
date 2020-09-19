<?php
add_action( 'rest_api_init', function(){

	register_rest_route( 'wedding/v1', '/domain-data/', [
		'methods'  => 'GET',
		'callback' => 'rest_api_domain_data_callback',
		'args'     => array(
			'arg_str' => array(
				'type'     => 'string', // значение параметра должно быть строкой
				'required' => true,     // параметр обязательный
			),
		),
	] );



} );

function rest_api_domain_data_callback(WP_REST_Request $request) {
	$domain = $request->get_param('arg_str');
	$re = getDomainFieldsData($domain);
	wp_send_json_success($re);
}

function getDomainFieldsData($domain) {
	$get = new WP_Query([
		'post_type' => 'domains',
		'post_status'=> 'publish'
	]);

	$needed_id = null;

	foreach ($get->posts as $post) {
		if (strtolower($post->post_title) === strtolower($domain)) {
			$needed_id = $post->ID;
			break;
		}
	}

	if (is_null($needed_id)) wp_send_json_error(['msg' => 'domain not exist']);

	$result = [];
	$order = [];

	$result['menu']['items'] = changeArrayIndex(get_field('site_header_menu', $needed_id), 'title', 'name');
	$result['menu']['fontsize'] = get_field('site_header_menu_item_font-size', $needed_id);
	$result['menu']['phone'] = get_field('site_phone', $needed_id);
	$result['menu']['logo'] = get_field('site_logo', $needed_id);

	$intro = get_field('intro', $needed_id);
	$result['introBlock']['title'] = $intro['text'];
	$result['introBlock']['textcolor'] = $intro['text_color'];
	$result['introBlock']['background'] = $intro['background_intro'];
	$order['intro_order'] = get_field('intro_order', $needed_id);

	$tentBlock = get_field('site_tent_wedding', $needed_id);
	$result['tentBlock']['title'] = $tentBlock['title'];
	$result['tentBlock']['guests'] = $tentBlock['guests'];
	$result['tentBlock']['minPrice'] = $tentBlock['minPrice'];
	$result['tentBlock']['characters'] = removeTextIndexFromArray($tentBlock['characters'], 'item');
	$result['tentBlock']['imgs'] = $tentBlock['imgs'];
	$result['tentBlock']['file_banket'] = $tentBlock['file_banket'];
	$result['tentBlock']['file_furshet'] = $tentBlock['furshet_file'];
	$order['site_tent_wedding_order'] = get_field('site_tent_wedding_order', $needed_id);

	$banquetHalls = get_field('bankets', $needed_id);
	$result['banquetHalls_title'] = $banquetHalls['title'];
	if (is_array($banquetHalls['list'])) {
		foreach ($banquetHalls['list'] as $k => $hall) {
			$st = [
				'title' => $hall['title'],
				'guests' => $hall['guest_count'],
				'minPrice' => $hall['min'],
				'characters' => removeTextIndexFromArray($hall['details'], 'text'),
				'imgs' => $hall['slider'],
				'maxGuests' => $hall['guest_count_max'],
				'file_banket' => $hall['banket_menu_file'],
				'file_furshet' => $hall['furshet_menu_file']
			];
			$result['banquetHalls'][] = $st;
		}
	}
	$order['bankets_order'] = get_field('bankets_order', $needed_id);

	$servie = get_field('wedding_service', $needed_id);
	$result['services_title'] = $servie['title'];
	if (is_array($servie['service'])) {
		foreach ($servie['service'] as $k => $s) {
			$st = [
				'title' => $s['title'],
				'anons' => $s['short_description'],
				'content' => $s['full_description'],
				'price' => $s['price'],
				'presentPrice' => $s['summ_price'],
				'imgs' => $s['gallery'],
			];
			$result['services'][] = $st;
		}
	}
	$order['wedding_service_order'] = get_field('wedding_service_order', $needed_id);

	$specialOffers = get_field('special_proposition', $needed_id);
	$result['specialOffers_title'] = $specialOffers['title'];
	if (is_array($specialOffers['service'])) {
		foreach ($specialOffers['service'] as $k => $s) {
			$st = [
				'title' => $s['title'],
				'anons' => $s['short_description'],
				'content' => $s['full_description'],
				'anonsList' => [
					['title' => 'Количество участников:', 'value' => $s['guest_count']],
					['title' => 'Стоимость:', 'value' => $s['stoemost']],
				],
				'imgs' => $s['gallery'],
				'btn' => ['content' => 'Узнать подробности']
			];
			$result['specialOffers'][] = $st;
		}
	}
	$order['special_proposition_order'] = get_field('special_proposition_order', $needed_id);

	$presents = get_field('presents_block', $needed_id);
	$result['presents_title'] = $presents['title'];
	if (is_array($presents['list'])) {
		foreach ($presents['list'] as $k => $present) {
			$st = [
				'title' => $present['title'],
				'img' => ['url' => $present['image']],
				'terms' => removeTextIndexFromArray($present['texts'], 'text')
			];
			$result['presents'][] = $st;
		}
	}
	$order['presents_block_order'] = get_field('presents_block_order', $needed_id);

	$calculate = get_field('calculator', $needed_id);
	$result['calculate']['title'] = $calculate['title'];
	$result['calculate']['guests']['min'] = (int) $calculate['guest_min'];
	$result['calculate']['guests']['max'] = (int) $calculate['guest_max'];
	$result['calculate']['checks'] = removeTextIndexFromArray(explode(',', $calculate['checks']));

	if (is_array($calculate['services'])) {
		foreach ($calculate['services'] as $k => $s) {
			$st = [
				'title' => $s['title'],
				'price' => (int) $s['price'],
				'signature' => $s['signature'],
				'pricePresent' => [
					'simple' => (int) $s['simple'],
					'month' => arrayIntegerValue($s['month']),
					'week' => arrayIntegerValue($s['week']),
				]
			];
			$result['calculate']['services'][] = $st;
		}
	}
	$order['calculator_order'] = get_field('calculator_order', $needed_id);

	$additionalServices = get_field('more_service', $needed_id);
	$result['additionalServices_title'] = get_field('more_service_title', $needed_id);
	$result['additionalServices'] = removeTextIndexFromArray($additionalServices, 'item');
	$order['more_service_order'] = get_field('more_service_order', $needed_id);

	$galleryRow = get_field('gallerys', $needed_id);
	$result['galleryRows']['title'] = get_field('gallery_block_title', $needed_id);
	foreach ($galleryRow as $n=> $gal) {
		$result["galleryRows"]['rows'][$n]['imgs'] = $gal['gallery'];
	}
	$order['gallery_order'] = get_field('gallery_order', $needed_id);

	$callback =  get_field('callback', $needed_id);
	$result['callback']['title'] = $callback['title'];
	$result['callback']['subtitle'] = $callback['subtitle'];
	$result['callback']['offers'] = removeTextIndexFromArray($callback['offers'], 'offer');
	$order['callback_order'] = get_field('callback_order', $needed_id);

	$map = get_field('map', $needed_id);
	$result['map']['coords'] = filter_ar_coordinates($map['coordinates']);
	$result['map']['title'] = $map['title'];
	$order['map_order'] = get_field('map_order', $needed_id);

	$footer = get_field('footer', $needed_id);
	$result['footer']['address'] = $footer['address'];
	$result['footer']['phone'] = $footer['phone'];
	$result['footer']['housr'] = $footer['hours'];
	$result['footer']['email'] = $footer['email'];
	if (is_array($footer['social'])) {
		foreach ($footer['social'] as $soc) {
			$result['footer']['social'][] = [
				'link' => $soc['link'],
				'img' => ['url' => $soc['image']]
			];
		}
	}

	$result['orders'] = $order;

	return $result;

}

function changeArrayIndex(array $arr, $ind_new, $ind_old) {
	//	var_dump($arr);die;
	$res = [];
	foreach ($arr as $n => $el) {
		foreach ($el as $k => $i) {
			if ($k === $ind_old) {
				$res[$n][$ind_new] = $i;
			} else {
				$res[$n][$k] = $i;
			}
		}
	}
	return $res;
}

function arrayIntegerValue($array) {
	$res = [];
	foreach ($array as $n => $el) {
		$res[$n] = (int) $el;
	}
	return $res;
}

function filter_ar_coordinates(string $string) {
	$res = [];
	foreach (explode(', ', $string) as $el) {
		$res[] = (float) ltrim($el);
	}
	return $res;
}

function convertstringJsonToJson(string $string) {
	$string = trim(str_replace([' ', "\r\n", '{', '}'], '', $string));
	$data = [];
	foreach (explode(',', $string) as $row) {
		$items = explode(':', $row);
		if (is_null($items[1])) continue;
		$data[$items[0]] = (int) $items[1];
	}
	return $data;
}

function removeTextIndexFromArray(array $array, $ind = false) {
	$res = [];
	foreach ($array as $el) {
		if ($ind === false) {
			if (is_numeric($el)) {
				$res[] = (int) $el;
			} else {
				$res[] = $el;
			}
		} else {
			if (is_numeric($el[$ind])) {
				$res[] = (int) $el[$ind];
			} else {
				$res[] = $el[$ind];
			}
		}

	}
	return $res;
}

add_action( 'admin_menu', 'remove_default_post_type' );

function remove_default_post_type() {
	remove_menu_page( 'edit.php' );
}

// Remove +New post in top Admin Menu Bar
add_action( 'admin_bar_menu', 'remove_default_post_type_menu_bar', 999 );

function remove_default_post_type_menu_bar( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'new-post' );
}

// Remove Quick Draft Dashboard Widget
add_action( 'wp_dashboard_setup', 'remove_draft_widget', 999 );

function remove_draft_widget(){
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
}