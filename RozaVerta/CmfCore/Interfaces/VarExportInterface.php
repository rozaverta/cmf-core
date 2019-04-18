<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.03.2019
 * Time: 17:22
 */

namespace RozaVerta\CmfCore\Interfaces;

interface VarExportInterface
{
	public function getArrayForVarExport(): array;

	static public function __set_state( $data );
}