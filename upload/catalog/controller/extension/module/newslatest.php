<?php
class ControllerExtensionModuleNewslatest extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/newslatest');

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$data['products'] = array();

		$filter_data = array(
			'sort'  => 'p.date_added',
			'order' => 'DESC',
			'start' => 0,
			'limit' => $setting['limit']
		);

		$results = $this->model_catalog_product->getProducts($filter_data);

		$sLanguageCode = $this->session->data['language'];
		switch ($sLanguageCode) {
			case 'ru-ru':
				$iLanguageId = 4;
				break;
			case 'en-gb':
				$iLanguageId = 1;
				break;
			default:
				$iLanguageId = 1;
				break;
		}

		$this->load->model('catalog/information');
		$top_news = $this->model_catalog_information->getTopNews( $iLanguageId );
		
		$aResult = [];
		foreach ($top_news as $key => $value) {

			$aData = [];
			if(is_array($value)){
				foreach ($value as $key2 => $value2) {
					//echo $key2 . " : " . $value2 . "<br>";
					$aData[$key2] = html_entity_decode( $value2 );
				}
				$aResult[] = $aData;
			}
		}
		
		$data['top_news'] = $aResult;

		if ($results) {
			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height']);
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = $result['rating'];
				} else {
					$rating = false;
				}

				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id']),
					// дополнительные поля для новости
					'date_added'  => $result['date_added'],
					'newslatest'  => $result['newslatest'],
					'show_newslatest' => $result['show_newslatest'],	
				);
			}

			return $this->load->view('extension/module/newslatest', $data);
		}
	}
}
