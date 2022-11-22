<?php

  class pm_square_checkout {
    public $id = __CLASS__;
    public $name = 'Square Checkout';
    public $description = '';
    public $author = 'Mishael Ochu';
    public $version = '1.0';
    public $website = 'https://www.squareup.com/';
    public $priority = 0;

    public function options($items, $subtotal, $tax, $currency_code, $customer) {

    // If not enabled
      if (empty($this->settings['status'])) return;

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
      }

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
          'quick_pay' => [
            'location_id' => settings::get('store_name'),
            'name' => settings::get('store_name'),
            'price_money' => [
              'amount' => $order->data['payment_due'],
              'currency' => $order->data['currency_code'],
            ],
          ],
        ];

        $result = $this->_call('POST', '/online-checkout/payment-links', $request);

        session::$data['square']['id'] = $result['payment']['id'];

        return [
          'method' => 'GET',
          'action' => $result['url'],
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

        $result = $this->_call('GET', '/online-checkout/payment-links/'. session::$data['square']['id']);

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

      $client = new wrap_http();

      $headers = [
        'Square-Version' => '2022-11-16'X,
        'Authorization' => 'Bearer '. $this->settings['secret_key'],
        'Content-Type' => 'application/json',
      ];

      $response = $client->call($method, 'https://connect.squareup.com/v2'.$endpoint, $request, $headers);

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
          'key' => 'icon',
          'default_value' => 'images/payment/cards.png',
          'title' => language::translate(__CLASS__.':title_icon', 'Icon'),
          'description' => language::translate(__CLASS__.':description_icon', 'Path to an image to be displayed.'),
          'function' => 'text()',
        ],
        [
          'key' => 'publishable_key',
          'default_value' => '',
          'title' => language::translate(__CLASS__.':title_publishable_key', 'Publishable Key'),
          'description' => language::translate(__CLASS__.':description_publishable_key', 'Your publishable key obtained by Stripe.'),
          'function' => 'text()',
        ],
        [
          'key' => 'secret_key',
          'default_value' => '',
          'title' => language::translate(__CLASS__.':title_secret_key', 'Secret Key'),
          'description' => language::translate(__CLASS__.':description_secret_key', 'Your secret key obtained by Stripe.'),
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
