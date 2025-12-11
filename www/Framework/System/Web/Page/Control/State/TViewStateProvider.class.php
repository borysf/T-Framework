<?php
namespace System\Web\Page\Control\State;

use Exception;
use System\Debug\TDebug;
use System\Security\Encryption\TEncrypt;
use System\Security\TSecurityException;

class TViewStateProvider implements IViewStateProvider {
    private readonly string $__encryptionKey;

    public function __construct(string $encryptionKey = '') {
        $this->__encryptionKey = $encryptionKey;

        if (strlen($encryptionKey) < 10) {
            TDebug::warn('Using empty or too short encryption key can lead to Viewstate vulnerabilities. The $encryptionKey should be at least 10 characters long');
        }
    }

    public function write(mixed $state) : string {
        return wordwrap(TEncrypt::encrypt(gzencode(serialize($state), 9), $this->__encryptionKey), 128, "\n", true);
    }

    public function read(string $state) : mixed {
        try {
            return unserialize(gzdecode(TEncrypt::decrypt($state, $this->__encryptionKey)));

        } catch (Exception $e) {
            throw new TSecurityException('Viewstate post data decryption failed');
        }
    }
}