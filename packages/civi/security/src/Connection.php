<?php

declare(strict_types=1);

namespace Civi\Security;

use DateTime;

class Connection
{
    public static function remoteHttp($app = ''): Connection
    {
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        // Ejemplo de cómo obtener el primer lenguaje preferido
        $languages = explode(',', $acceptLanguage);

        $remoteTarget = $_SERVER['SERVER_NAME'] ?? gethostname();

        // Verificar si está disponible la IP del cliente en X-Forwarded-For (usualmente en proxies)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // X-Forwarded-For puede contener una lista de IPs, tomamos la primera
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $clientIp = trim($ipList[0]);
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            // X-Real-IP también puede ser usado por algunos proxies
            $clientIp = $_SERVER['HTTP_X_REAL_IP'];
        } else {
            // REMOTE_ADDR es la dirección IP del cliente en una conexión directa
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        if ($clientIp === '::1') {
            $clientIp = '127.0.0.1';
        }
        return new Connection(
            remote: true,
            startTime: new \DateTime(),
            application: $app,
            endpoint: $_SERVER['REQUEST_URI'],
            sourceIp: $clientIp,
            targetIp: $remoteTarget,
            locale: $languages[0] ?? ''
        );
    }

    public function __construct(
        public readonly bool $remote,
        public readonly DateTime $startTime,
        public readonly string $application,
        public readonly string $endpoint,
        public readonly string $sourceIp,
        public readonly string $targetIp,
        public readonly ?string $locale
    ) {
    }

    public function inRange(string $cidr): bool
    {
        // Separar la IP base de la máscara de red
        list($subnet, $mask) = explode('/', $cidr);

        // Convertir IP y subnet a formato de número entero de 32 bits
        $ipDecimal = ip2long($this->sourceIp);
        $subnetDecimal = ip2long($subnet);

        // Crear la máscara de red en formato decimal
        $maskDecimal = ~((1 << (32 - $mask)) - 1);

        // Comparar la IP y la subnet con la máscara de red aplicada
        return ($ipDecimal & $maskDecimal) === ($subnetDecimal & $maskDecimal);
    }
}
