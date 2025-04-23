<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View\Toolkit\Bootstrap;

class NavLink
{
    public function __construct(private readonly ?string $url, private readonly string $body)
    {

    }
    public function render(): string
    {
        $active = $this->isActive();
        return "<li class=\"nav-item\"><a class=\"nav-link" . ($active ? " active" : "") . "\" href=\"" . ($this->url ?? "#") . "\">{$this->body}"
            . ($active ? "<span class=\"visually-hidden\">(current)</span>" : "")
            . "</a></li>";
    }

    private function isActive(): bool
    {
        if ($this->url) {
            $__nav_href = $this->url;
            $__current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            return rtrim($__current_path, '/') === rtrim($__nav_href, '/');
        } else {
            return false;
        }
    }
}