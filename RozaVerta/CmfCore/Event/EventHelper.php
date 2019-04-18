<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 10.09.2018
 * Time: 13:37
 */

namespace RozaVerta\CmfCore\Event;

use RozaVerta\CmfCore\Database\DatabaseManager as DB;
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Schemes\Events_SchemeDesigner;

final class EventHelper
{
	private function __construct() {}

	/**
	 * Validate the event name
	 *
	 * @param string $name
	 * @return bool
	 */
	static public function validName(string $name): bool
	{
		$len = strlen($name);
		return $len > 0
			&& $len < 256
			&& substr($name, 0, 2) === "on"
			&& ctype_upper($name[2])
			&& ! preg_match('/[^a-zA-Z]/', $name);
	}

	/**
	 * Validate the event name for module
	 *
	 * @param string $name
	 * @param ModuleInterface $module
	 * @return bool
	 */
	static public function validModuleName( string $name, ModuleInterface $module): bool
	{
		if( !self::validName($name) )
		{
			return false;
		}

		if($module->getId() === 1)
		{
			return true;
		}

		$pref = "on" . $module->getName(); // for User module event name = onUserEventName
		$len = strlen($pref);
		return strlen($name) > $len && substr($name, 0, $len) === $pref && ctype_upper($name[$len]);
	}

	/**
	 * Check event exists. If event exists set event ID and module ID link
	 *
	 * @param string $name
	 * @param null $eventId
	 * @param null $moduleId
	 * @return bool
	 */
	static public function exists( string $name, & $eventId = null, & $moduleId = null ): bool
	{
		if( !self::validName($name) )
		{
			return false;
		}

		$row = DB
			::table(Events_SchemeDesigner::getTableName())
			->where("name", $name)
			->select(["id", "module_id"])
			->first();

		if( !$row )
		{
			return false;
		}

		$eventId = (int) $row->get("id");
		$moduleId = (int) $row->get("module_id");

		return true;
	}

	/**
	 * Get event scheme designer
	 *
	 * @param string $name
	 *
	 * @return Events_SchemeDesigner|null
	 */
	static public function getSchemeDesigner( string $name ): ?Events_SchemeDesigner
	{
		if( ! self::validName($name) )
		{
			return null;
		}

		/** @var Events_SchemeDesigner $row */
		$row = DB
			::table(Events_SchemeDesigner::class)
			->where("name", $name)
			->first();

		if( !$row )
		{
			return null;
		}

		return $row;
	}
}