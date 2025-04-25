<?php

declare(strict_types=1);

namespace Civi\Micro\Management;

class HealthDetail
{

    public static function up(string $name, ?array $details = null): HealthDetail
    {
        return new HealthDetail(name: $name, status: 'UP', details: $details);
    }

    public static function down(string $name, ?array $details = null): HealthDetail
    {
        return new HealthDetail(name: $name, status: 'DOWN', details: $details);
    }

    public static function unknown(string $name, ?array $details = null): HealthDetail
    {
        return new HealthDetail(name: $name, status: 'UNKWOWN', details: $details);
    }

    private function __construct(
        public readonly string $status,
        public readonly string $name,
        public readonly ?array $details
    ) {}
}
