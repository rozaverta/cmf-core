<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2016
 * Time: 21:50
 */

namespace RozaVerta\CmfCore\Cli;

use RozaVerta\CmfCore\Cli\IO\SymfonyInputOutput;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Traits\ServiceTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCliCommand extends Command
{
	use Traits\SystemHostTrait;
	use ServiceTrait;

	/**
	 * @var Prop
	 */
	protected $docs;

	/**
	 * @var InputInterface
	 */
	protected $input;

	/**
	 * @var OutputInterface
	 */
	protected $output;

	/**
	 * AbstractCliCommand constructor.
	 *
	 * @param array $docs
	 * @throws \RozaVerta\CmfCore\Exceptions\ClassNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	public function __construct( array $docs = [] )
	{
		$this->thisServices();
		$this->docs = new Prop($docs);
		parent::__construct( $docs["name"] );
		$this->init();
	}

	// input and output

	protected function configure()
	{
		// the short description shown while running "php bin/console list"
		if( $this->docs->has( "description" ) )
		{
			$this->setDescription($this->docs["description"]);
		}

		// the full command description shown when running the command with
		// the "--help" option
		if( $this->docs->has( "help" ) )
		{
			$this->setHelp($this->docs["help"]);
		}
	}

	protected function execute( InputInterface $input, OutputInterface $output )
	{
		$this->setIO(new SymfonyInputOutput($this, $input, $output));
		$this->input = $input;
		$this->output = $output;
		$this->exec();
	}

	protected function init() {}

	abstract protected function exec();
}