<?php

declare(strict_types=1);

namespace Civi\View;

class ViewSection
{
    public function __construct(public readonly string $application, public readonly string $label, public readonly string $path)
    {
    }
}
