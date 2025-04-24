<?php declare(strict_types=1);

namespace Civi\Repomanager\Shared\Infrastructure\View;

class ScriptExtractor
{
    private array $fragments = [];

    public function extractAndReplace(string $html): array
    {
        $pattern = '#<script>(.*?)</script>#is';
        preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE);

        if (!$matches[1]) {
            return ['html' => $html, 'scripts' => []];
        }

        $this->fragments = [];
        $offset = 0;
        $result = '';

        foreach ($matches[1] as $i => [$content, $pos]) {
            $start = strpos($html, '<script>', $offset);
            $end = strpos($html, '</script>', $start) + strlen('</script>');

            $this->fragments[] = $content;
            $result .= substr($html, $offset, $start - $offset);
            $result .= "<!-- inline-script-$i -->";
            $offset = $end;
        }

        $result .= substr($html, $offset);

        return ['html' => $result, 'scripts' => $this->fragments];
    }

    public function restoreWithScriptTag(string $html, string $scriptSrc): string
    {
        $html = str_replace(
            array_map(fn($i) => "<!-- inline-script-$i -->", array_keys($this->fragments)),
            '',
            $html
        );

        return preg_replace(
            '#</body>#i',
            '<script src="' . $scriptSrc . '"></script></body>',
            $html
        );
    }
}