<?php

declare(strict_types=1);

namespace Civi\Store\JsonDb;

class JsonDbConfig
{
    public readonly string $userName;
    public readonly string $userEmail;

    public function __construct(
        public readonly ?string $backupToken = null,
        public readonly ?string $backupRemote = null,
        ?string $userName = null,
        ?string $userEmail = null
    ){
        $this->userName = $userName ?? 'Storage Backup';
        $this->userEmail = $userEmail ?? 'backup@localhost';
    }
}