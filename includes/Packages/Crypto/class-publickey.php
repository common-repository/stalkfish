<?php

namespace Stalkfish\Packages\Crypto;

class PublicKey {
	/** @var resource */
	protected $public_key;

	/**
	 * @param string $publicKeyString
	 * @return static
	 * @throws \Exception
	 */
	public static function fromString( $publicKeyString )
	{
		return new static( $publicKeyString );
	}

	/**
	 * @param string $pathToPublicKey
	 * @return static
	 * @throws \Exception
	 */
	public static function fromFile( $pathToPublicKey )
	{
		if ( ! file_exists( $pathToPublicKey ) ) {
			throw new \Exception("There is no file at path: `{$pathToPublicKey}`.");
		}

		$publicKeyString = file_get_contents( $pathToPublicKey );

		return new static( $publicKeyString );
	}

	/**
	 * @throws \Exception
	 */
	public function __construct( $publicKeyString ) {
		$this->public_key = openssl_pkey_get_public( $publicKeyString );

		if ($this->public_key === false) {
			throw new \Exception('This does not seem to be a valid public key.');
		}
	}

	public function encrypt(string $data)
	{
		openssl_public_encrypt($data, $encrypted, $this->public_key, OPENSSL_PKCS1_OAEP_PADDING);

		return $encrypted;
	}

	public function canDecrypt(string $data): bool
	{
		try {
			$this->decrypt($data);
		} catch (\Exception $exception) {
			return false;
		}

		return true;
	}

	/**
	 * @throws \Exception
	 */
	public function decrypt(string $data): string
	{
		openssl_public_decrypt($data, $decrypted, $this->public_key, OPENSSL_PKCS1_PADDING);

		if (is_null($decrypted)) {
			throw new \Exception('Could not decrypt the data.');
		}

		return $decrypted;
	}

	public function details(): array
	{
		return openssl_pkey_get_details($this->public_key);
	}

	public function verify(string $data, string $signature): bool
	{
		return openssl_verify($data, base64_decode($signature), $this->public_key, OPENSSL_ALGO_SHA256);
	}
}
