<?php

  class pm_square_checkout {
    public $id = __CLASS__;
    public $name = 'Square Checkout';
    public $description = '';
    public $author = 'Mishael Ochu';
    public $version = '1.0';
    public $website = 'https://www.squareup.com/';
    public $priority = 0;
    public $production_url = 'https://connect.squareup.com/v2';
    public $sandbox_url = 'https://connect.squareupsandbox.com/v2';
    public $base_url = '';

    public function options($items, $subtotal, $tax, $currency_code, $customer) {

    // If not enabled
      if (empty($this->settings['status'])) return;

    // disable for forbidden options (only works with Check Box styled options
      $forbidden_options = preg_split('#\s*,\s*#', $this-> settings['forbidden_options']);
      if (!is_bool($forbidden_options)) {
        foreach ($forbidden_options as $forbidden_option) {
          foreach ($items as $item) {
            $options = $item['options'];

            if (isset($options[$forbidden_option])) {
              return;
            }
          }
        }
      }     

      $options = [];

        $options[] = [
          'id' => 'square',
          'icon' => 'images/payment/square.jpg',
          'name' => 'Square',
          'description' => language::translate(__CLASS__.':description', 'Payments processing with Square'),
          'fields' => '',
          'cost' => 0,
          'tax_class_id' => 0,
          'confirm' => language::translate(__CLASS__.':title_pay_now', 'Pay Now'),
        ];

      return [
        'title' => $this->name,
        'options' => $options,
      ];
    }

    public function transfer($order) {

      try {
        $order->save(); // Create order ID

        $request = [
          'idempotency_key' => uniqid(),
          'redirect_url' => document::ilink('order_process'),
          'pre_populate_buyer_email' => $order->data['customer_email'],
          'cancel_url' => document::ilink('checkout'),
          'order' => [
            'location_id' => $this->settings['location_id'],
            'reference_id' => $order->data['id'],
            'name' => settings::get('store_name'),
            'line_items' => [],
            'price_money' => [
              'amount' => $order->data['payment_due'],
              'currency' => $order->data['currency_code'],
            ],
          ],
        ];

        foreach ($order->data['items'] as $item) {
           if ($item['price'] <= 0) continue;
           $request['line_items'][] = [
            'name' => $item['name'],
            'quantity' => (float)$item['quantity'],
            'base_price_money' => [
              'amount' => $this->_amount($item['price'] + $item['tax'], $order->data['currency_code'], $order->data['currency_value']),
              'currency' => $order->data['currency_code'],
            ],
           ];
        }

        $result = $this->_call('POST', '/online-checkout/payment-links', $request);

        session::$data['square']['order_id'] = $result['payment_link']['order_id'];

        return [
          'method' => 'GET',
          'action' => $result['payment_link']['url'],
        ];

      } catch (Exception $e) {
        return ['error' => $e->getMessage()];
      }
    }

    public function verify($order) {

      try {
        if (empty(session::$data['square']['order_id'])) {
          throw new Exception('Missing order id');
        }

        $result = $this->_call('GET', '/orders/'. session::$data['square']['order_id']);

        if (empty($result)) {
          throw new Exception('Failed to create payment link');
        }

        return [
          'order_status_id' => $this->settings['order_status_id'],
          'order_id' => $result['order_id'],
        ];

      } catch (Exception $e) {
        return ['error' => $e->getMessage()];
      }
    }

    private function _amount($amount, $currency_code, $currency_value) {

    // Zero-decimal currencies
      if (in_array($currency_code, explode(',', 'BIF,CLP,DJF,GNF,JPY,KMF,KRW,MGA,PYG,RWF,UGX,VND,VUV,XAF,XOF,XPF'))) {
        return currency::format_raw($amount, $currency_code, $currency_value);
      }

      return currency::format_raw($amount, $currency_code, $currency_value) * 100;
    }

    private function _call($method, $endpoint, $request = null) {
      if (empty($this->settings['is_production'])) {
         $base_url = $sandbox_url;
      } else {
        $base_url = $production_url;
      }
      $client = new wrap_http();

      $headers = [
        'Square-Version' => '2023-07-20',
        'Authorization' => 'Bearer '. $this->settings['access_token'],
        'Content-Type' => 'application/json',
      ];

      $response = $client->call($method, $base_url.$endpoint, $request, $headers);

      if (!$result = json_decode($response, true)) {
        throw new Exception('Invalid response from remote machine');
      }

      if (!empty($result['error'])) {
        throw new Exception($result['error']['message']);
      }

      return $result;
    }

    function settings() {
      return [
        [
          'key' => 'status',
          'default_value' => '1',
          'title' => language::translate(__CLASS__.':title_status', 'Status'),
          'description' => language::translate(__CLASS__.':description_status', 'Enables or disables the module.'),
          'function' => 'toggle("e/d")',
        ],
        [
          'key' => 'is_production',
          'default_value' => '0',
          'title' => language::translate(__CLASS__.':title_is_production', 'Is Production Mode'),
          'description' => language::translate(__CLASS__.':description_status', 'Enables production mode or uses the sanbox for the module.'),
          'function' => 'toggle("y/n")',
        ],
        [
          'key' => 'icon',
          'default_value' => 'images/payment/square.jpg',
          'title' => language::translate(__CLASS__.':title_icon', 'Icon'),
          'description' => language::translate(__CLASS__.':description_icon', 'Path to an image to be displayed.'),
          'function' => 'text()',
        ],
        [
          'key' => 'forbidden_options',
          'default_value' => '',
          'title' => language::translate(__CLASS__.':title_forbidden_options', 'Forbidden Options'),
          'description' => language::translate(__CLASS__.':description_forbidden_options', 'A comma separated list of payment options for which this module should be disabled.'),
          'function' => 'text()',
        ],
        [
          'key' => 'application_id',
          'default_value' => '',
          'title' => language::translate(__CLASS__.':title_application_id', 'Application ID'),
          'description' => language::translate(__CLASS__.':description_application_id', 'Your application ID obtained from Square.'),
          'function' => 'text()',
        ],
        [
          'key' => 'access_token',
          'default_value' => '',
          'title' => language::translate(__CLASS__.':title_access_token', 'Access Token'),
          'description' => language::translate(__CLASS__.':description_access_token', 'Your access token obtained from  Square.'),
          'function' => 'text()',
        ],
        [
          'key' => 'location_id',
          'default_value' => '',
          'title' => language::translate(__CLASS__.':title_location_id', 'Location ID'),
          'description' => language::translate(__CLASS__.':description_location_id', 'Your location id obtained from  Square.'),
          'function' => 'text()',
        ],
        [
          'key' => 'priority',
          'default_value' => '0',
          'title' => language::translate(__CLASS__.':title_priority', 'Priority'),
          'description' => language::translate(__CLASS__.':description_priority', 'Process this module in the given priority order.'),
          'function' => 'int()',
        ],
      ];
    }

    public function install() {}

    public function uninstall() {}
  }
