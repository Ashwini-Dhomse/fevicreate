<?php

namespace App\Service;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class OtpService
{
    private $cache;

    public function __construct()
    {
        $this->cache = new FilesystemAdapter();
    }

    public function generateOtp(string $identifier): string
    {
        $otp = rand(100000, 999999);

        $this->cache->getItem('otp_' . $identifier)
            ->set($otp)
            ->expiresAfter(300); // 5 min
        $this->cache->save($this->cache->getItem('otp_' . $identifier));

        return $otp;
    }

    public function verifyOtp(string $identifier, string $otp): bool
    {
        $item = $this->cache->getItem('otp_' . $identifier);

        if (!$item->isHit()) {
            return false;
        }

        return $item->get() == $otp;
    }
}
