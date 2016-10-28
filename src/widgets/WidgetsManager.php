<?php
namespace beatrix\widgets;

use League\Url\Url;

class WidgetsManager
{
    private $widgets = [];

    public function set($slot, $content)
    {
        $this->widgets[$slot] = $content;
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
     *
     * @param array $sections Definitions of sections grouped by path regexps
     *
     * @return $this
     */
    public function defaults(array $sections)
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
            $this->widgets[$sectionId] = $section;
        }
        return $this;
    }

    /**
     * Does template have all passed section names/wildcards
     *
     * @param string ...$name Section names with wildcards to check
     *
     * @return bool
     */
    public function hasAll()
    {
        $names = array_filter(array_map('strval', func_get_args()));
        foreach ($names as $name) {
            if (preg_match('/\*/', $name)) {
                $secNames = array_keys($this->widgets);
                foreach ($secNames as $secName) {
                    if (!$this->wildcardMatch($name, $secName)) {
                        return false;
                    }
                }
            } else {
                if (!isset($this->widgets[$name]) || !$this->widgets[$name]) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param string ...$name Section names with wildcards to check
     *
     * @return bool
     */
    public function hasAny()
    {
        $names = array_filter(array_map('strval', func_get_args()));
        foreach ($names as $name) {
            if (preg_match('/\*/', $name)) {
                $secNames = array_keys($this->widgets);
                foreach ($secNames as $secName) {
                    $wildcardMatch = $this->wildcardMatch($name, $secName);
                    if ($wildcardMatch && $this->widgets[$secName]) {
                        return true;
                    }
                }
            } else {
                if (isset($this->widgets[$name]) && $this->widgets[$name]) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $test    Pattern containing * symbol as "any symbols" wildcard
     * @param string $subject String to test against
     *
     * @return bool
     */
    private function wildcardMatch($test, $subject)
    {
        $pattern = '/' . str_replace('*', '.+', $test) . '/';
        return (bool)preg_match($pattern, $subject);
    }

    public function menu($id, $options)
    {
        //todo inject into Blade like @menu
    }
}
