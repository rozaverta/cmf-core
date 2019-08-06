<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 21:28
 */

namespace RozaVerta\CmfCore\Http\Events;

use RozaVerta\CmfCore\Http\Response;
use JsonSerializable;

/**
 * Class ResponseJsonEvent
 *
 * @property array|JsonSerializable $json       The data to encode as JSON
 * @property string $prefix                     The name of the JSON-P function prefix
 *
 * @package RozaVerta\CmfCore\Http\Events
 */
class ResponseJsonEvent extends ResponseSendEvent
{
	public function __construct( Response $response, $json, $prefix )
	{
		parent::__construct($response);
		$this->params['json'] = $json;
		$this->params['prefix'] = $prefix;
		$this->params_allowed[] = 'json';
		$this->params_allowed_type['json'] = static function($value) {
			return is_array($value) || $value instanceof JsonSerializable;
		};
	}
}