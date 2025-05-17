<?php

namespace ModXengine;

use Exception;
use ModXengine\Parser\ParseVariable;
use ModXengine\Environment\Environment;
use ModXengine\Cache\TemplateCache;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ModXEngine
{
    private Environment $environment;

    private TemplateCache $templateCache;

    protected FileSystem $fileSystem;

    private $data = [];
    private $layout = null;
    private $loopVars = [];
    private $processedComponents = [];

    public function __construct(Environment $environment, TemplateCache $templateCache)
    {

        if (!$environment instanceof Environment) {
            throw new Exception('Invalid environment provided.');
        }
        if (!$templateCache instanceof TemplateCache) {
            throw new Exception('Invalid template cache provided.');
        }

        $this->environment =  $environment;
        $this->templateCache = $templateCache;
        $this->fileSystem = new FileSystem();
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function with(array $data)
    {
        if(!is_array($data)){
            throw new Exception("Only Array Data is Used WIth the With() method, Use the set() method for non array data");
        }

        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function layout($layout)
    {
        $this->layout = $layout;
        return $this;
    }

    public function render($template, ?int $ttl = null)
    {
        $file = $this->getTemplatesFiles("{$template}.modx", 'component');
        return $this->templateCache->getCachedTemplate(
            $template,
            function (array $context) use ($file) {
                // Combine template content
                $content = $this->combineTemplates($file);

                // Extract data for PHP execution
                extract($context, EXTR_SKIP);

                // Create temporary file for execution
                $tempFile = tempnam(sys_get_temp_dir(), 'modx_');
                if ($tempFile === false) {
                    throw new Exception('Failed to create temporary file for rendering.');
                }

                file_put_contents($tempFile, $content);

                // Capture output
                ob_start();
                try {
                    include $tempFile;
                } catch (Exception $e) {
                    throw new Exception("Error rendering template: " . $e->getMessage());
                } finally {
                    unlink($tempFile);
                }
                $output = ob_get_clean();

                // Validate output
                if (empty($output)) {
                    throw new Exception("Rendered output is empty for template {$file}.");
                }

                return $output;
            },
            $this->data,
            $ttl
        );
    
    }

    private function combineTemplates($templateFile)
    {
        $this->processedComponents = [];
        $this->loopVars = [];

        // Read main template content
        $templateContent = file_get_contents($templateFile);

        // Process layout directive and combine with layout
        $hasLayout = false;
        $content = preg_replace_callback(
            '/<:\s*layout\s*[\'"](\w+)[\'"]\s*:>(.*)/s',
            function ($matches) use (&$hasLayout) {
                $hasLayout = true;
                $this->layout = $matches[1];
                $layoutFile = $this->getTemplatesFiles("{$this->layout}.modx", 'layouts');
                if (!file_exists($layoutFile)) {
                    throw new Exception("Layout {$this->layout} not found at $layoutFile");
                }
                // Read layout content
                $layoutContent = file_get_contents($layoutFile);

                // Use the template content after the layout directive
                $templateContent = $matches[2];
                /**Replace <:content:> or <?php echo $content; ?> with template content */
                $layoutContent = str_replace('<:content:>', $templateContent, $layoutContent);
                $layoutContent = preg_replace(
                    '/<\?php\s*echo\s*\$content;\s*\?>/',
                    $templateContent,
                    $layoutContent
                );
                return $layoutContent;
            },
            $templateContent
        );

        // If no layout directive, use template content as is
        if (!$hasLayout) {
            $content = $templateContent;
        }

        // Process all directives iteratively
        $content = $this->processContent($content);

        return $content;
    }

    private function processContent($content)
    {
        $previousContent = '';
        // Iterate until no more changes (handles nested components)
        while ($content !== $previousContent) {
            $previousContent = $content;
            // Process for loops
            $content = $this->processForLoops($content);
            $content = $this->processComments($content);
            // Process components
            $content = $this->processComponents($content);

            // Process variables
            $content = ParseVariable::transformContenVars($content, $this->data, $this->loopVars);
        }
        return $content;
    }

    private function processForLoops($content)
    {
        return preg_replace_callback(
            '/<:for\s+(\w+)\s*(?:\s*,\s*(\w+))?\s*in\s+(\w+)\s*:>(.*?)<:endfor:>/s',
            function ($matches) {
                $item = $matches[1];
                $index = $matches[2] ?? '__index';
                $array = $matches[3];
                $body = $matches[4];

                $this->loopVars[$item] = true;
                $this->loopVars[$index] = true;

                return '<?php foreach ($' . $array . ' as $' . $index . ' => $' . $item . '): ?>' . $body . '<?php endforeach; ?>';
            },
            $content
        );
    }

    private function processComments($content)
    {
        return preg_replace_callback(
            '/#\s*(.*?)\s*#/s',
            function ($matches) {
               return "<!-- $matches[1] -->";;
            },
            $content
        );
    }

    private function parseComponentDirective($content, &$offset)
    {
        $startTag = '<:component';
        $endTag = '>';
        $result = ['found' => false];

        // Find start of component directive
        $startPos = strpos($content, $startTag, $offset);


        if ($startPos === false) {
            return $result;
        }

        // Find end of component directive
        $endPos = strpos($content, $endTag, $startPos);
        if ($endPos === false) {

            return $result;
        }

        // Extract directive
        $directive = substr($content, $startPos, $endPos - $startPos + strlen($endTag));
        $innerContent = substr($content, $startPos + strlen($startTag), $endPos - $startPos - strlen($startTag));

        // Parse component name and props
        $parts = preg_split('/\s+/', trim($innerContent), 2);
        if (empty($parts[0])) {

            return $result;
        }

        $componentName = $parts[0];
        $propsRaw = isset($parts[1]) ? trim($parts[1]) : '';

        // Parse props
        $props = [];
        if ($propsRaw) {
            $propPairs = [];
            $currentProp = '';
            $inQuotes = false;
            $quoteChar = '';
            $i = 0;

            while ($i < strlen($propsRaw)) {
                $char = $propsRaw[$i];
                if ($char === '"' || $char === "'") {
                    if ($inQuotes && $char === $quoteChar) {
                        $inQuotes = false;
                        $quoteChar = '';
                    } else if (!$inQuotes) {
                        $inQuotes = true;
                        $quoteChar = $char;
                    }
                    $currentProp .= $char;
                } else if ($char === ' ' && !$inQuotes) {
                    if ($currentProp) {
                        $propPairs[] = $currentProp;
                        $currentProp = '';
                    }
                } else {
                    $currentProp .= $char;
                }
                $i++;
            }
            if ($currentProp) {
                $propPairs[] = $currentProp;
            }

            foreach ($propPairs as $pair) {
                if (preg_match('/(\w+|:\w+)\s*=\s*[\'"]([^\'"]*)[\'"]/i', $pair, $match)) {
                    $key = $match[1][0] === ':' ? substr($match[1], 1) : $match[1];
                    $value = $match[1][0] === ':' ? ($this->data[$match[2]] ?? null) : $match[2];
                    $props[$key] = $value;
                }
            }
        }

        $result = [
            'found' => true,
            'componentName' => $componentName,
            'props' => $props,
            'startPos' => $startPos,
            'length' => $endPos - $startPos + strlen($endTag),
        ];


        $offset = $endPos + strlen($endTag);
        return $result;
    }

    private function processComponents($content)
    {

        $offset = 0;
        $output = '';
        $lastPos = 0;

        while ($offset < strlen($content)) {

            $parseResult = $this->parseComponentDirective($content, $offset);
            if (!$parseResult['found']) {
                $output .= substr($content, $lastPos);
                break;
            }

            // Append content before the directive
            $output .= substr($content, $lastPos, $parseResult['startPos'] - $lastPos);

            // Process the component
            $componentName = $parseResult['componentName'];
            $props = $parseResult['props'];

            if (isset($this->processedComponents[$componentName])) {

                $output .= "<!-- Component $componentName already processed (avoiding recursion) -->";
            } else {
                $this->processedComponents[$componentName] = true;

                $componentFile = $this->getTemplatesFiles(trim($componentName, "\"") . ".modx", 'components');
                $componentContent = file_get_contents($componentFile);


                // Add props to component scope
                $componentData = array_merge($this->data, $props);
                $originalData = $this->data;
                $this->data = $componentData;
                $processedContent = $this->processComponentContent($componentContent);
                $this->data = $originalData;
                $output .= $processedContent;
            }

            $lastPos = $parseResult['startPos'] + $parseResult['length'];
        }

        return $output;
    }



    private function processComponentContent($content)
    {
        // Process all directives in component content
        return $this->processContent($content);
    }

    private function getTemplatesFiles(string $file, string $type = 'template'): string
    {
        foreach ($this->environment->getPaths() as $templateDir) {
            if ($type == 'layouts') {
                if ($this->fileSystem->exists($templateDir . 'layouts/' . $file)) {
                    return Path::normalize($templateDir . 'layouts/' . $file);
                }
            }

            if ($type == 'components') {
                if ($this->fileSystem->exists($templateDir . 'components/' . $file)) {

                    return Path::normalize($templateDir . 'components/' . $file);
                }
            }
            if ($this->fileSystem->exists($templateDir . $file)) {
                return Path::normalize($templateDir . $file);
            }
        }

        $error = ucfirst($type) . " $file Not Found, We Have search In \n\t";
        foreach ($this->environment->getPaths() as $templateDir) {
            $error .= "\t\t $templateDir";
        }

        throw new Exception($error, 1);
    }
}
