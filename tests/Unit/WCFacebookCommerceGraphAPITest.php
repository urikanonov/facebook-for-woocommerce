<?php
declare( strict_types=1 );

use SkyVerge\WooCommerce\PluginFramework\v5_10_0\SV_WC_API_Exception;

class WCFacebookCommerceGraphAPITest extends WP_UnitTestCase {

	/** @var WC_Facebookcommerce_Graph_API */
	private $api;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api = new WC_Facebookcommerce_Graph_API( 'test-api-key-9678djyad552' );
	}

	public function test_api_has_authorisation_header_with_proper_api_key() {
		$api = new WC_Facebookcommerce_Graph_API( 'test-api-key-09869asfdasf56' );
		$response = function( $result, $parsed_args ) {
			$this->assertArrayHasKey( 'headers', $parsed_args );
			$this->assertArrayHasKey( 'Authorization', $parsed_args['headers'] );
			$this->assertEquals( 'Bearer test-api-key-09869asfdasf56', $parsed_args['headers']['Authorization'] );
			return [];
		};
		add_filter( 'pre_http_request', $response, 10, 2 );

		/* Call any api, does not matter much, all the api calls must have Auth header. */
		$api->is_product_catalog_valid( 'product-catalog-id-654129' );
	}

	/**
	 * Implementing current test using get_catalog() method.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function test_process_response_body_parses_response_body() {
		$expected = [
			'id'     => '2536275516506259',
			'name'   => 'Facebook for WooCommerce 2 - Catalog',
			'custom' => 'John Doe',
		];

		$response = function() {
			return [
				'body' => '{"name":"Facebook for WooCommerce 2 - Catalog","custom":"John Doe","id":"2536275516506259"}'
			];
		};
		add_filter( 'pre_http_request', $response );

		$data = $this->api->get_catalog( '2536275516506259' );

		$this->assertEquals( $expected, $data );
	}

	/**
	 * Implementing current test using get_catalog() method.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function test_process_response_body_throws_an_exception_when_gets_connection_wp_error() {
		$this->expectException( Exception::class );
		$this->expectExceptionCode( 007 );
		$this->expectExceptionMessage( 'WP Error Message' );

		$response = function() {
			return new WP_Error( 007, 'WP Error Message' );
		};
		add_filter( 'pre_http_request', $response );

		$this->api->get_catalog( '2536275516506259' );
	}

	/**
	 * Implementing current test using get_catalog() method.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function test_process_response_body_throws_an_exception_when_there_is_no_response_body() {
		$this->expectException( JsonException::class );
		$this->expectExceptionMessage( 'Syntax error' );

		$response = function() {
			return [];
		};
		add_filter( 'pre_http_request', $response );

		$this->api->get_catalog( '2536275516506259' );
	}

	public function test_is_product_catalog_valid_returns_true() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/product-catalog-id-654129', $url );
			return [
				'response' => [
					'code' => 200,
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$is_valid = $this->api->is_product_catalog_valid( 'product-catalog-id-654129' );

		$this->assertTrue( $is_valid );
	}

	public function test_is_product_catalog_valid_returns_false() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/product-catalog-id-654129', $url );
			return [
				'response' => [
					'code' => 400,
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$is_valid = $this->api->is_product_catalog_valid( 'product-catalog-id-654129' );

		$this->assertFalse( $is_valid );
	}

	public function test_is_product_catalog_valid_throws_an_error() {
		$this->expectException( SV_WC_API_Exception::class );
		$this->expectExceptionCode( 007 );
		$this->expectExceptionMessage( 'message' );

		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/product-catalog-id-2174129410', $url );
			return new WP_Error( 007, 'message' );
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$this->api->is_product_catalog_valid( 'product-catalog-id-2174129410' );
	}

	public function test_get_catalog_returns_catalog_id_and_name() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/2536275516506259?fields=name', $url );
			return [
				'body' => '{"name":"Facebook for WooCommerce 2 - Catalog","id":"2536275516506259"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK'
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$data = $this->api->get_catalog( '2536275516506259' );

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );

		$this->assertEquals( 'Facebook for WooCommerce 2 - Catalog', $data['name'] );
		$this->assertEquals( '2536275516506259', $data['id'] );
	}

	public function test_get_user_must_return_facebook_user_id() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/me', $url );
			return [
				'body' => '{"id":"2525362755165069"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK'
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$data = $this->api->get_user();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertEquals( '2525362755165069', $data['id'] );
	}

	public function test_revoke_user_permission_must_result_success() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'DELETE', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/2525362755165069/permissions/manage_business_extension', $url );
			return [
				'body' => '{"success":true}',
				'response' => [
					'code'    => 200,
					'message' => 'OK'
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->revoke_user_permission( '2525362755165069', 'manage_business_extension' );

		$this->assertArrayhasKey( 'success', $result );
		$this->assertTrue( $result['success'] );
	}

	public function test_send_item_updates_returns_handles() {
		$items = [
			'allow_upsert' => true,
			'requests'     => [
				[
					'method' => 'UPDATE',
					'data'   => [
						'id'                           => 'woo-vneck-tee-red_26',
						'title'                        => 'V-Neck T-Shirt',
						'description'                  => 'short product description \u05ea\u05d9\u05d0\u05d5\u05e8 \u05de\u05d5\u05e6\u05e8 \u05e7\u05e6\u05e8',
						'image_link'                   => 'https://wordpress-facebook.ddev.site/wp-content/uploads/2022/04/vneck-tee-2.jpg',
						'link'                         => 'https://wordpress-facebook.ddev.site/product/v-neck-t-shirt/?attribute_pa_color=red&attribute_pa_size=customvariable-size&attribute_semicolon=Third%3Ahigh',
						'price'                        => '20 TRY',
						'availability'                 => 'in stock',
						'visibility'                   => 'published',
						'sale_price_effective_date'    => '1970-01-29T00:00+00:00/1970-01-30T23:59+00:00',
						'sale_price'                   => '20 TRY',
						'google_product_category'      => '1604',
						'size'                         => 'Custom:variable size',
						'color'                        => 'Red',
						'item_group_id'                => 'woo-vneck-tee_12',
						'condition'                    => 'new',
						'additional_variant_attribute' => 'Semicolon:Third high',
					],
				],
				[
					'method' => 'UPDATE',
					'data'   => [
						'id'                           => 'woo-vneck-tee-green_27',
						'title'                        => 'V-Neck T-Shirt',
						'description'                  => 'short product description \u05ea\u05d9\u05d0\u05d5\u05e8 \u05de\u05d5\u05e6\u05e8 \u05e7\u05e6\u05e8',
						'image_link'                   => 'https://wordpress-facebook.ddev.site/wp-content/uploads/2022/04/vneck-tee-green-1.jpg',
						'link'                         => 'https://wordpress-facebook.ddev.site/product/v-neck-t-shirt/?attribute_pa_color=green&attribute_pa_size=customvariable-size&attribute_semicolon=Second%3Amid',
						'price'                        => '20 TRY',
						'availability'                 => 'in stock',
						'visibility'                   => 'published',
						'sale_price_effective_date'    => '1970-01-29T00:00+00:00/1970-01-30T23:59+00:00',
						'sale_price'                   => '20 TRY',
						'google_product_category'      => '1604',
						'size'                         => 'Custom:variable size',
						'color'                        => 'Green',
						'item_group_id'                => 'woo-vneck-tee_12',
						'condition'                    => 'new',
						'additional_variant_attribute' => 'Semicolon:Second mid',
					],
				],
			],
			'item_type'    => 'PRODUCT_ITEM',
		];

		$response = function( $result, $parsed_args, $url ) use ( $items ) {
			$this->assertEquals( 'POST', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/2536275516506259/items_batch', $url );
			$body = [
				'allow_upsert' => true,
				'requests'     => json_encode( $items ),
				'item_type'    => 'PRODUCT_ITEM',
			];
			$this->assertEquals( $body, $parsed_args['body'] );
			return [
				'body'     => '{"handles":["AcyF-IFFFMif2xx6oUlkHF7qbutTBr0Q2jjWRNfDNXD_VjontQqZp79tt0GL03L3nqoYRrv5RpqDaC8WCoB0jLtG"]}',
				'response' => [
					'code'    => 200,
					'message' => 'OK'
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->send_item_updates( '2536275516506259', $items );

		$this->assertArrayHasKey( 'handles', $result );
		$this->assertEquals( ['AcyF-IFFFMif2xx6oUlkHF7qbutTBr0Q2jjWRNfDNXD_VjontQqZp79tt0GL03L3nqoYRrv5RpqDaC8WCoB0jLtG'], $result['handles'] );
	}

	public function test_send_pixel_events_sends_pixel_events() {
		$data = [
			'action_source'    => 'website',
			'event_time'       => '1652769366',
			'event_id'         => '4061a42a-4b12-479e-be51-25ad8a19f640',
			'event_name'       => 'Purchase',
			'event_source_url' => 'https://wordpress-facebook.ddev.site/checkout/',
			'custom_data'      => [
				'num_items'        => '1',
				'content_ids'      => [ 'woo-belt_17' ],
				'content_name'     => [ 'Belt' ],
				'content_type'     => 'product',
				'contents'         => ['{"id":"woo-belt_17","quantity":1}'],
				'value'            => '55.00',
				'currency'         => 'TRY',
				'content_category' => 'Accessories',
			],
			'user_data' => [
				'client_ip_address' => '172.20.0.1',
				'client_user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',
				'em'                => 'a95869aaee45119b0a46d9b3d5f2d788cc25995af4291fc0841afa71097004e3',
				'external_id'       => 'b86b273ff34fce19d6b804eff5a3f5747ada4eaa22f1d49c01e52ddb7875b4b',
				'ct'                => '6195198aeec54576f52474bf92cb02ec1c5e117d1dd9ddbceb08f5bfc545a0b8',
				'zp'                => 'b83c588da0c6931625f42e0948054a3ade722bfd02c27816305742ed7390ac6c',
				'st'                => '6959097001d10501ac7d54c0bdb8db61420f658f2922cc26e46d536119a31126',
				'ph'                => '63640264849a87c90356129d99ea165e37aa5fabc1fea46906df1a7ca50db492',
				'country'           => '79adb2a2fce5c6ba215fe5f27f532d4e7edbac4b6a5e09e1ef3a08084a904621',
				'click_id'          => 'fb.1.1650980285743.IwAR3UfvR6kpWQLdR7trcx0Xbc-G6P-4pNota-g8WGnBmtA6w_JMfSBvjZZrM',
				'browser_id'        => 'fb.1.1650401327550.1150300680',
			],
		];

		$events = [
			new \SkyVerge\WooCommerce\Facebook\Events\Event( $data )
		];

		$response = function( $result, $parsed_args, $url ) use ( $events ) {
			$this->assertEquals( 'POST', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v12.0/1964583793745557/events', $url );

			$body = [
				'data' => array_map(
					function ( $item ) {
						$event_data = $item->get_data();
						if ( isset( $event_data['user_data']['click_id'] ) ) {
							$event_data['user_data']['fbc'] = $event_data['user_data']['click_id'];
							unset( $event_data['user_data']['click_id'] );
						}
						if ( isset( $event_data['user_data']['browser_id'] ) ) {
							$event_data['user_data']['fbp'] = $event_data['user_data']['browser_id'];
							unset( $event_data['user_data']['browser_id'] );
						}
						return $event_data;
					},
					$events
				),
				'partner_agent' => 'woocommerce-' . WC()->version . '-' . WC_Facebook_Loader::PLUGIN_VERSION,
			];
			$this->assertEquals( $body, $parsed_args['body'] );
			return [
				'body'     => '{"events_received":1,"messages":[],"fbtrace_id":"ACkWGi-ptHPA897dD0liZEg"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK'
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->send_pixel_events( '1964583793745557', $events );

		$this->assertFalse( has_filter( 'wc_facebook_api_pixel_event_request_data' ) );
		$this->assertArrayHasKey( 'events_received', $result );
		$this->assertEquals( 1, $result['events_received'] );
	}

	public function test_send_pixel_events_applies_filter_to_pixel_events_data() {
		$data = [
			'action_source' => 'website',
			'custom_data'   => [
				'value' => '55.00',
			]
		];

		$events = [
			new \SkyVerge\WooCommerce\Facebook\Events\Event( $data )
		];

		$filter = function( $data ) {
			$data['data'][0]['action_source']        = 'universe';
			$data['data'][0]['custom_data']['value'] = '1,000,000.00';
			return $data;
		};
		add_filter( 'wc_facebook_api_pixel_event_request_data', $filter );

		$response = function( $result, $parsed_args, $url ) use ( $events ) {
			$this->assertEquals( 'universe', $parsed_args['body']['data'][0]['action_source'] );
			$this->assertEquals( '1,000,000.00', $parsed_args['body']['data'][0]['custom_data']['value'] );
			return [
				'body'     => '{"events_received":1,"messages":[],"fbtrace_id":"ACkWGi-ptHPA897dD0liZEg"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK'
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$result = $this->api->send_pixel_events( '1964583793745557', $events );

		$this->assertTrue( has_filter( 'wc_facebook_api_pixel_event_request_data' ) );
		$this->assertArrayHasKey( 'events_received', $result );
		$this->assertEquals( 1, $result['events_received'] );
	}
}

/**
 * $response = [
		'headers'       => $args['response_headers'],
		'body'          => json_encode( $args['response_body'] ),
		'response'      => [
			'code'    => $args['response_code'],
			'message' => $args['response_message'],
		],
		'cookies'       => [],
		'http_response' => null,
	];
 */
