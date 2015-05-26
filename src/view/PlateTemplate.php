<?php
namespace beatrix\view;

use League\Plates\Template\Template;
use League\Url\Url;

/**
 * Extends Plate Template with lazy rendering, sections traversing, section recording.
 */
class PlateTemplate extends Template
{
    /**
     * Does template have all passed section names/wildcards
     * @param string ...$name Section names with wildcards to check
     * @return bool
     */
    public function hasAllSections()
    {
        $names = array_filter(array_map('strval', func_get_args()));
        foreach ($names as $name) {
            if (preg_match('/\*/', $name)) {
                $secNames = array_keys($this->sections);
                foreach ($secNames as $secName) {
                    if (!$this->wildcardMatch($name, $secName)) {
                        return false;
                    }
                }
            } else {
                if (!isset($this->sections[$name]) || !$this->sections[$name]) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param string ...$name Section names with wildcards to check
     * @return bool
     */
    public function hasAnySection()
    {
        $names = array_filter(array_map('strval', func_get_args()));
        foreach ($names as $name) {
            if (preg_match('/\*/', $name)) {
                $secNames = array_keys($this->sections);
                foreach ($secNames as $secName) {
                    $wildcardMatch = $this->wildcardMatch($name, $secName);
                    if ($wildcardMatch && $this->sections[$secName]) {
                        return true;
                    }
                }
            } else {
                if (isset($this->sections[$name]) && $this->sections[$name]) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $test Pattern containing * symbol as "any symbols" wildcard
     * @param string $subject String to test against
     * @return bool
     */
    private function wildcardMatch($test, $subject)
    {
        $pattern = '/' . str_replace('*', '.+', $test) . '/';
        return (bool)preg_match($pattern, $subject);
    }

    /**
     * Start section recording.
     * @param string $name
     */
    public function startSection($name)
    {
        $this->start($name);
    }

    /**
     * End section recording
     */
    public function stopSection()
    {
        $this->stop();
    }

    /**
     * Append string to section, create if not exist
     * @param string $name
     * @param string $content
     */
    public function sectionAppend($name, $content)
    {
        $this->setSection($name, $this->section($name, '') . $content);
    }

    /**
     * Prepend string to section, create if not exist
     * @param string $name
     * @param string $content
     */
    public function sectionPrepend($name, $content)
    {
        $this->setSection($name, $content . $this->section($name, ''));
    }

    /**
     * Set pre-rendered or lazy-render content under section name
     * @param string $name
     * @param string|array|Callable $content
     * @return $this
     */
    public function setSection($name, $content)
    {
        $this->sections[$name] = $content;
        return $this;
    }

    /**
     * Set default template's sections values (or any lazy-render configurations).
     * Resulting section set depends of current http request path,
     * rules must be ordered from most common to more specific.
     *
     * Expects config like this:
     *     [
     *       '/' => ['slot1' => 'default', 'slot2' => ['template', [vars]], 'slot3' => function(){...}],
     *       '/news' => ['slot2' => '']
     *     ]
     * @param array $sections Definitions of sections grouped by path regexps
     * @return $this
     */
    public function sectionsDefaults(array $sections)
    {
        $result = array();
        $path = '/' . ltrim(Url::createFromServer($_SERVER)->getPath(), '/');
        foreach ($sections as $rule => $sectionSet) {
            $re = ltrim(rtrim($rule, '$ '), ' ^/');
            $re = '@^/' . $re . '$@';
            if (preg_match($re, $path)) {
                // replace slots with latest rules
                foreach ($sectionSet as $secId => $config) {
                    $result[$secId] = $sectionSet[$secId];
                }
            }
        }
        foreach ($result as $sectionId => $section) {
            $this->sections[$sectionId] = $section;
        }
        return $this;
    }


    /**
     * Set template to be used as layout.
     *
     * WARNING: Template MUST contain `$this->section('content')` call.
     *
     * @param string|null $name Template name to be used as layout. If it is null, then no layout will be applied.
     * @param array $data Layout data
     * @return $this
     */
    public function setLayout($name, array $data = array())
    {
        $this->layout($name, $data);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function section($name, $default = null)
    {
        if (isset($this->sections[$name])) {
            $this->sections[$name] = $this->lazyRender($this->sections[$name]);
            return $this->sections[$name];
        } else {
            return $this->lazyRender($default);
        }
    }

    /**
     * Renders section by demand if it was configured with array or closure.
     * @param string|Callable|array $delayedContent
     * @return string
     */
    protected function lazyRender($delayedContent)
    {
        $rendered = '';
        if (is_string($delayedContent)) {
            $rendered = $delayedContent;
        } elseif ($delayedContent instanceof \Closure) {
            $rendered = (string)$delayedContent();
        } elseif (is_array($delayedContent)) {
            $template = $delayedContent[0];
            $data = array_get($delayedContent, 1, array());
            $rendered = $this->fetch($template, $data);
        }
        return $rendered;
    }

}
