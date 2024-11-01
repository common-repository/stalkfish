<?php

namespace Stalkfish\ErrorTracker\Base;

use Stalkfish\Dependencies\Symfony\Component\VarDumper\Cloner\VarCloner;
use Stalkfish\Dependencies\Symfony\Component\VarDumper\Dumper\HtmlDumper;

class View {

	/** @var string */
	private $file;

	/** @var array */
	private $data = array();

	public function __construct( string $file, array $data = array() ) {
		$this->file = $file;
		$this->data = $data;
	}

	public static function create( string $file, array $data = array() ): self {
		return new static( $file, $data );
	}

	private function dumpViewData( $variable ): string {
		$cloner = new VarCloner();

		$dumper = new HtmlDumper();
		$dumper->setDumpHeader( '' );

		$output = fopen( 'php://memory', 'r+b' );

		$dumper->dump(
			$cloner->cloneVar( $variable )->withMaxDepth( 1 ),
			$output,
			array(
				'maxDepth'        => 1,
				'maxStringLength' => 160,
			)
		);

		return stream_get_contents( $output, -1, 0 );
	}

	public function toArray() {
		return array(
			'file' => $this->file,
			'data' => array_map( array( $this, 'dumpViewData' ), $this->data ),
		);
	}
}
