<?php

namespace RozaVerta\CmfCore\View\Interfaces;

interface ModifierInterface
{
	public function format($value, array $attributes = []);
}