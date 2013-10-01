<?php

class ControllerPaymentPayU extends Controller
{
  private function getFieldsAsString($fields)
  {
    $string = '';

    foreach ($fields as $field)
    {
      if (is_array($field))
      {
        $string .= $this->getFieldsAsString($field); 
      }
      else
      {
        $string .= mb_strlen($field, '8bit') . $field;
      }
    }

    return $string;
  }

  private function getHash($fields)
  {
    return hash_hmac('md5', $this->getFieldsAsString($fields), $this->config->get('payu_secretkey'));
  }

  protected function index()
  {
    $this->data['button_confirm'] = $this->language->get('button_confirm');
    $this->data['button_back'] = $this->language->get('button_back');

    $this->data['action'] = $this->config->get('payu_LU');

    $this->load->model('checkout/order');

    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

    $fields = array();
    $string = '';

    $fields['MERCHANT'] = $this->config->get('payu_merchant');
    $fields['ORDER_REF'] = $order_info['order_id'];
    $fields['ORDER_DATE'] = date('Y-m-d H:i:s');

    $fields['ORDER_PNAME'] = array();
    $fields['ORDER_PCODE'] = array();
    $fields['ORDER_PINFO'] = array();
    $fields['ORDER_PRICE'] = array();
    $fields['ORDER_QTY'] = array();
    $fields['ORDER_VAT'] = array();
    
    $products_total = 0;
    foreach ($this->cart->getProducts() as $product)
    {
      $fields['ORDER_PNAME'][] = $product['name'];
      $fields['ORDER_PCODE'][] = $product['product_id'];
      $fields['ORDER_PINFO'][] = $product['model'];
      $fields['ORDER_PRICE'][] = $product['price'];
      $fields['ORDER_QTY'][] = $product['quantity'];
      $fields['ORDER_VAT'][] = $this->config->get('payu_vat');

      $products_total += $product['price'] * $product['quantity'];
    }

    $fields['ORDER_SHIPPING'] = $order_info['total'] - $products_total;
    $fields['PRICES_CURRENCY'] = $this->config->get('payu_currency');
    $fields['DESTINATION_CITY'] = $order_info['shipping_city'];
    $fields['DESTINATION_STATE'] = $order_info['shipping_zone'];
    $fields['DESTINATION_COUNTRY'] = $order_info['shipping_country'];

    $fields['ORDER_HASH'] = $this->getHash($fields);

    if ($this->config->get('payu_debug') == 1)
    {
      $fields['TESTORDER'] = 'TRUE';
      $fields['DEBUG'] = 1;
    }
    else
    {
      $fields['TESTORDER'] = 'FALSE';
      $fields['DEBUG'] = 0;
    }

    $fields['LANGUAGE'] = $this->config->get('payu_language');

    $this->load->model('localisation/country');

    $fields['BILL_FNAME'] = $order_info['payment_firstname'];
    $fields['BILL_LNAME'] = $order_info['payment_lastname'];
    $fields['BILL_EMAIL'] = $order_info['email'];
    $fields['BILL_PHONE'] = $order_info['telephone'];
    $fields['BILL_FAX'] = $order_info['fax'];
    $fields['BILL_ADDRESS'] = $order_info['payment_address_1'];
    $fields['BILL_ADDRESS2'] = $order_info['payment_address_2'];
    $fields['BILL_ZIPCODE'] = $order_info['payment_postcode'];
    $fields['BILL_CITY'] = $order_info['payment_city'];
    $fields['BILL_STATE'] = $order_info['payment_zone'];

    $country = $this->model_localisation_country->getCountry($order_info['payment_country_id']);
    if ($country)
    {
      $fields['BILL_COUNTRYCODE'] = $country['iso_code_2']; 
    }

    $fields['DELIVERY_FNAME'] = $order_info['shipping_firstname'];
    $fields['DELIVERY_LNAME'] = $order_info['shipping_lastname'];
    $fields['DELIVERY_EMAIL'] = $order_info['email'];
    $fields['DELIVERY_PHONE'] = $order_info['telephone'];
    $fields['DELIVERY_FAX'] = $order_info['fax'];
    $fields['DELIVERY_ADDRESS'] = $order_info['shipping_address_1'];
    $fields['DELIVERY_ADDRESS2'] = $order_info['shipping_address_2'];
    $fields['DELIVERY_ZIPCODE'] = $order_info['shipping_postcode'];
    $fields['DELIVERY_CITY'] = $order_info['shipping_city'];
    $fields['DELIVERY_STATE'] = $order_info['shipping_zone'];

    $country = $this->model_localisation_country->getCountry($order_info['shipping_country_id']);
    if ($country)
    {
      $fields['DELIVERY_COUNTRYCODE'] = $country['iso_code_2']; 
    }

    $fields['BACK_REF'] = $this->config->get('payu_backref');

    $this->data['fields'] = $fields;

    $this->id = 'payment';

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/payu.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/payu.tpl';
		} else {
			$this->template = 'default/template/payment/payu.tpl';
		}	
		
		$this->render();	
  }

  public function confirm()
  {
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		if(!$order_info) return;

    if ($order_info['order_status_id'] == 0)
    {
      $this->model_checkout_order->confirm($order_info['order_id'], $this->config->get('payu_order_status_id_progress'), 'PayU');
    }
  }

  public function callback()
  {
    if (!isset($_POST['REFNOEXT']))
      return;

    $fields = $_POST;
    unset($fields['HASH']);

    $hash = $this->getHash($fields);

    if ($_POST['HASH'] != $hash)
    {
      header('HTTP/1.1 403 Unauthorized');
      exit;
    }

    $order_id = $_POST['REFNOEXT'];

    $this->load->model('checkout/order');

    $order_info = $this->model_checkout_order->getOrder($order_id);

    if ($order_info['order_status_id'] == 0)
    {
      $this->model_checkout_order->confirm($order_id, $this->config->get('payu_order_status_id'), 'PayU');
    }
    else
    {
      $this->model_checkout_order->update($order_id, $this->config->get('payu_order_status_id'), 'PayU', true);
    }

    $fields = array();
    $fields['IPN_PID'] = $_POST['IPN_PID'][0];
    $fields['IPN_PNAME'] = $_POST['IPN_PNAME'][0];
    $fields['IPN_DATE'] = $_POST['IPN_DATE'];
    $fields['DATE'] = date('YmdHis');

    $hash = $this->getHash($fields);

    printf('<EPAYMENT>%s|%s</EPAYMENT>', $fields['DATE'], $hash);
    exit;
  }
};
