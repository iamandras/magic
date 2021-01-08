<?php

declare(strict_types=1);

namespace MagicFramework\Core;

class ViewHandler
{
    private array $blocks = [];

    public function render(string $path, array $parameters): string
    {
        $cachedFilePath = $this->cache($path);
        ob_start();
        extract($parameters, EXTR_SKIP);
        include $cachedFilePath;

        return ob_get_clean();
    }

    private function cache($filePath): string
    {
        $cachePath = constant('BASE_PATH') . '/public/cache/';
        if (!file_exists($cachePath)) {
            mkdir($cachePath, 0744);
        }

        $cachedFilePath = $cachePath . str_replace(array('/', '.html'), array('_', ''), $filePath . '.php');
        $code = self::includeFiles($filePath);
        $code = self::compileCode($code);
        file_put_contents($cachedFilePath, '<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $code);

        return $cachedFilePath;
    }

    private function includeFiles(string $file): string
    {
        $code = file_get_contents(constant('BASE_PATH') . '/src/View/' . $file);
        preg_match_all('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', $code, $matches, PREG_SET_ORDER);

        foreach ($matches as $value) {
            $code = str_replace($value[0], self::includeFiles($value[2]), $code);
        }

        $code = preg_replace('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', '', $code);

        return $code;
    }

    private function compileCode(string $code): string {
        $code = $this->compileBlock($code);
        $code = $this->compileYield($code);
        $code = $this->compileEscapedEchos($code);
        $code = $this->compileEchos($code);

        return $this->compilePHP($code);
    }

    private function compilePHP(string $code): string
    {
        return preg_replace('~\{%\s*(.+?)\s*\%}~is', '<?php $1 ?>', $code);
    }

    private function compileEchos(string $code): string
    {
        return preg_replace('~\{{\s*(.+?)\s*\}}~is', '<?php echo $1 ?>', $code);
    }

    private function compileEscapedEchos(string $code): string
    {
        return preg_replace('~\{{{\s*(.+?)\s*\}}}~is', '<?php echo htmlentities($1, ENT_QUOTES, \'UTF-8\') ?>', $code);
    }

    private function compileBlock(string $code): string
    {
        preg_match_all('/{% ?block ?(.*?) ?%}(.*?){% ?endblock ?%}/is', $code, $matches, PREG_SET_ORDER);

        foreach ($matches as $value) {
            if (!array_key_exists($value[1], $this->blocks)) {
                $this->blocks[$value[1]] = '';
            }
            if (strpos($value[2], '@parent') === false) {
                $this->blocks[$value[1]] = $value[2];
            } else {
                $this->blocks[$value[1]] = str_replace('@parent', $this->blocks[$value[1]], $value[2]);
            }
            $code = str_replace($value[0], '', $code);
        }

        return $code;
    }

    private function compileYield(string $code): string
    {
        foreach($this->blocks as $block => $value) {
            $code = preg_replace('/{% ?addBlock ?' . $block . ' ?%}/', $value, $code);
        }

        return preg_replace('/{% ?addBlock ?(.*?) ?%}/i', '', $code);
    }
}