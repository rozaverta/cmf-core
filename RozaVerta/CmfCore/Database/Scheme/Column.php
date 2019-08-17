<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.03.2019
 * Time: 19:41
 */

namespace RozaVerta\CmfCore\Database\Scheme;

use Doctrine\DBAL\Types\Type;
use JsonSerializable;
use RozaVerta\CmfCore\Helper\Json;
use RozaVerta\CmfCore\Interfaces\Arrayable;
use RozaVerta\CmfCore\Interfaces\Jsonable;
use RozaVerta\CmfCore\Interfaces\VarExportInterface;
use RozaVerta\CmfCore\Support\Prop;

class Column implements Arrayable, Jsonable, JsonSerializable, VarExportInterface
{
	use ExtraTrait;

	/** @var string */
	protected $name;

	/** @var string */
	protected $type;

	/** @var int|null */
	protected $length = null;

	/** @var int */
	protected $precision = 10;

	/** @var int */
	protected $scale = 0;

	/** @var bool */
	protected $unsigned = false;

	/** @var bool */
	protected $fixed = false;

	/** @var bool */
	protected $notNull = true;

	/** @var string|null */
	protected $default = null;

	/** @var bool */
	protected $autoIncrement = false;

	/** @var mixed[] */
	protected $platformOptions = [];

	/** @var string|null */
	protected $columnDefinition = null;

	/** @var string|null */
	protected $comment = null;

	public function __construct( string $name, array $options = [] )
	{
		$this->name = $name;
		$this->type = $options["type"] ?? Type::STRING;
		$this->extra = isset( $options['extra'] )
			? ( $options['extra'] instanceof Prop ? $options['extra'] : new Prop( $options['extra'] ) )
			: new Prop();

		if( isset($options['length']) ) $this->length = $options['length'];
		if( isset($options['precision']) ) $this->precision = $options['precision'];
		if( isset($options['scale']) ) $this->scale = $options['scale'];
		if( isset($options['unsigned']) ) $this->unsigned = $options['unsigned'];
		if( isset($options['fixed']) ) $this->fixed = $options['fixed'];
		if( isset($options['notNull']) ) $this->notNull = $options['notNull'];
		if( isset($options['autoIncrement']) ) $this->autoIncrement = $options['autoIncrement'];
		if( isset($options['platformOptions']) ) $this->platformOptions = $options['platformOptions'];
		if( isset($options['default']) ) $this->default = $options['default'];
		if( isset($options['columnDefinition']) ) $this->columnDefinition = $options['columnDefinition'];
		if( isset($options['comment']) ) $this->comment = $options['comment'];
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return int|null
	 */
	public function getLength(): ?int
	{
		return $this->length;
	}

	/**
	 * @return int
	 */
	public function getPrecision(): int
	{
		return $this->precision;
	}

	/**
	 * @return int
	 */
	public function getScale(): int
	{
		return $this->scale;
	}

	/**
	 * @return bool
	 */
	public function isUnsigned(): bool
	{
		return $this->unsigned;
	}

	/**
	 * @return bool
	 */
	public function isFixed(): bool
	{
		return $this->fixed;
	}

	/**
	 * @return bool
	 */
	public function isNotNull(): bool
	{
		return $this->notNull;
	}

	/**
	 * @return mixed
	 */
	public function getDefault()
	{
		return $this->default;
	}

	/**
	 * @return bool
	 */
	public function isDefault(): bool
	{
		return $this->default !== null;
	}

	/**
	 * @return bool
	 */
	public function isAutoIncrement(): bool
	{
		return $this->autoIncrement;
	}

	/**
	 * @return mixed[]
	 */
	public function getPlatformOptions(): array
	{
		return $this->platformOptions;
	}

	/**
	 * @return null|string
	 */
	public function getColumnDefinition(): ?string
	{
		return $this->columnDefinition;
	}

	/**
	 * @return bool
	 */
	public function isColumnDefinition(): bool
	{
		return $this->columnDefinition !== null;
	}

	/**
	 * @return null|string
	 */
	public function getComment(): ?string
	{
		return $this->comment;
	}

	/**
	 * @return bool
	 */
	public function isComment(): bool
	{
		return $this->comment !== null;
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$row = [
			'name' => $this->name,
			'type' => $this->type,
			'length' => $this->length,
			'precision' => $this->precision,
			'scale' => $this->scale,
			'unsigned' => $this->unsigned,
			'fixed' => $this->fixed,
			'notnull' => $this->notNull,
			'autoincrement' => $this->autoIncrement,
			'platformOptions' => $this->platformOptions,
			'extra' => $this->extra->toArray()
		];

		if( $this->isDefault() ) $row['default'] = $this->default;
		if( $this->isColumnDefinition() ) $row['columnDefinition'] = $this->columnDefinition;
		if( $this->isComment() ) $row['comment'] = $this->comment;

		return $row;
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int $options
	 * @param int $depth
	 * @return string
	 */
	public function toJson( $options = 0, $depth = 512 ): string
	{
		return Json::stringify( $this->jsonSerialize() );
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	public function getArrayForVarExport(): array
	{
		return $this->toArray();
	}

	static public function __set_state( $data )
	{
		return new Column($data["name"], $data);
	}
}