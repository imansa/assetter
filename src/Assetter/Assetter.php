<?php
/**
 * Copyright (c) 2016 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2016, Adam Banaszkiewicz
 * @link      https://github.com/requtize/assetter
 */
namespace Requtize\Assetter;

/**
 * Asseter class. Manage assets (CSS and JS) for website, and it's
 * dependencies by other assets. Allows load full lib by giving a name
 * or append custom library's files.
 *
 * @author Adam Banaszkiewicz https://github.com/requtize
 */
class Assetter
{
    /**
     * Stores collection of libraries to load.
     * @var array
     */
    protected $collection = [];

    /**
     * Stores global revision for all files.
     * Revision number is added automatically to all files as GET aparameter.
     * Allows refresh files from cache in browsers.
     * @var integer
     */
    protected $revision = 0;

    /**
     * Stores name of default group for library.
     * @var string
     */
    protected $defaultGroup = 'def';

    /**
     * Loaded libraries.
     * @var array
     */
    protected $loaded = [];

    /**
     * Store namespaces, which will be replaces when some asset will be loaded.
     * @var array
     */
    protected $namespaces = [];

    /**
     * Constructor.
     * @param array   $collection   Collection of assets.
     * @param integer $revision     Global revision number. Allows refresh files
     *                              in browsers Cache by adding get value to file path.
     *                              In example: ?rev=2
     * @param string  $default      Group name of default group of assets.
     */
    public function __construct(array $collection = [], $revision = 0, $defaultGroup = 'def')
    {
        $this->setRevision($revision);
        $this->setDefaultGroup($defaultGroup);
        $this->setCollection($collection);
    }

    /**
     * While cloning self, clears loaded libraries.
     * @return void
     */
    public function __clone()
    {
        $this->loaded = [];
    }

    /**
     * Return clone of this object, without loaded libraries in it.
     * @return Cloned self object.
     */
    public function doClone()
    {
        return clone $this;
    }

    /**
     * Register namespace.
     * @param  string $ns  Namespace name.
     * @param  string $def Namespace path.
     * @return self
     */
    public function registerNamespace($ns, $def)
    {
        $this->namespaces[$ns] = $def;

        return $this;
    }

    /**
     * Unregister namespace.
     * @param  string $ns namespace name.
     * @return self
     */
    public function unregisterNamespace($ns)
    {
        unset($this->namespaces[$ns]);

        return $this;
    }

    /**
     * Gets current global revision of files.
     * @return integer
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Sets global revision of files.
     * @param integer $revision Revision number.
     * @return self
     */
    public function setRevision($revision)
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * Gets current default global group for files that have not
     * defined in collection, or in append() array.
     * @return string
     */
    public function getDefaultGroup()
    {
        return $this->defaultGroup;
    }

    /**
     * Sets default group for files.
     * @param string $defaultGroup
     * @return self
     */
    public function setDefaultGroup($defaultGroup)
    {
        $this->defaultGroup = $defaultGroup;

        return $this;
    }

    /**
     * Returns full collection of registered assets.
     * @return array
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Sets collection of assets.
     * @param  array $collection
     * @return self
     */
    public function setCollection(array $collection)
    {
        foreach($collection as $asset)
        {
            $this->appendToCollection($asset);
        }

        return $this;
    }

    /**
     * Append asset array to collection. before this, apply required
     * indexes if not exists.
     * @param  array $asset $array with asset data.
     * @return self
     */
    public function appendToCollection(array $data)
    {
        $this->collection[] = [
            'order'    => isset($data['order']) ? $data['order'] : 0,
            'revision' => isset($data['revision']) ? $data['revision'] : $this->revision,
            'name'     => isset($data['name']) ? $data['name'] : uniqid(),
            'files'    => isset($data['files']) ? $data['files'] : [],
            'group'    => isset($data['group']) ? $data['group'] : $this->defaultGroup,
            'require'  => isset($data['require']) ? $data['require'] : []
        ];

        return $this;
    }

    /**
     * Loads assets from given name.
     * @param  string $name Name of library/asset.
     * @return self
     */
    public function load($data)
    {
        if(is_array($data))
        {
            $this->loadFromArray($data);
        }
        else
        {
            $this->loadFromCollection($data);
        }

        return $this;
    }

    /**
     * Loads given asset (by name) from defined collection.
     * @param  string $name Asset name.
     * @return self
     */
    public function loadFromCollection($name)
    {
        if($this->alreadyLoaded($name))
        {
            return $this;
        }

        foreach($this->collection as $item)
        {
            if($item['name'] === $name)
            {
                $this->loadFromArray($item);
            }
        }

        return $this;
    }

    /**
     * Load asset by given array. Apply registered namespaces for all
     * files' paths.
     * @param  array  $item Asset data array.
     * @return self
     */
    public function loadFromArray(array $data)
    {
        $item = [
            'order'    => isset($data['order']) ? $data['order'] : 0,
            'revision' => isset($data['revision']) ? $data['revision'] : $this->revision,
            'name'     => isset($data['name']) ? $data['name'] : uniqid(),
            'files'    => isset($data['files']) ? $data['files'] : [],
            'group'    => isset($data['group']) ? $data['group'] : $this->defaultGroup,
            'require'  => isset($data['require']) ? $data['require'] : []
        ];

        if(isset($item['files']['js']) && is_array($item['files']['js']))
            $item['files']['js'] = $this->applyNamespaces($item['files']['js']);
        if(isset($item['files']['css']) && is_array($item['files']['css']))
            $item['files']['css'] = $this->applyNamespaces($item['files']['css']);

        $this->loaded[] = $item;

        if(isset($item['require']) && is_array($item['require']))
        {
            foreach($item['require'] as $name)
            {
                $this->loadFromCollection($name);
            }
        }

        return $this;
    }

    /**
     * Check if given library name was already loaded.
     * @param  string $name Name of library/asset.
     * @return boolean
     */
    public function alreadyLoaded($name)
    {
        foreach($this->loaded as $item)
        {
            if($item['name'] === $name)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns both CSS and JS tags from given group name.
     * If group name is asterisk (*), will return from all loaded groups.
     * @param  string $group Group name.
     * @return string HTML tags as string.
     */
    public function all($group = '*')
    {
        $this->sort();

        return implode("\n", $this->getLoadedCssList($group))."\n".implode("\n", $this->getLoadedJsList($group));
    }

    /**
     * Returns CSS tags from given group name.
     * If group name is asterisk (*), will return from all loaded groups.
     * @param  string $group Group name.
     * @return string HTML tags as string.
     */
    public function css($group = '*')
    {
        $this->sort();

        return implode("\n", $this->getLoadedCssList($group));
    }

    /**
     * Returns JS tags from given group name.
     * If group name is asterisk (*), will return from all loaded groups.
     * @param  string $group Group name.
     * @return string HTML tags as string.
     */
    public function js($group = '*')
    {
        $this->sort();

        return implode("\n", $this->getLoadedJsList($group));
    }

    protected function applyNamespaces(array $files)
    {
        foreach($files as $key => $file)
        {
            $files[$key] = str_replace(array_keys($this->namespaces), array_values($this->namespaces), $file);
        }

        return $files;
    }

    protected function resolveGroup($group, $type)
    {
        if($group == null)
        {
            return $this->defaultGroup;
        }

        return $group;
    }

    protected function getLoadedCssList($group)
    {
        $group = $this->resolveGroup($group, 'css');

        $result = [];

        foreach($this->loaded as $item)
        {
            if($group != '*')
            {
                if($item['group'] != $group)
                {
                    continue;
                }
            }

            if(isset($item['files']['css']) && is_array($item['files']['css']))
            {
                foreach($item['files']['css'] as $file)
                {
                    $result[] = '<link rel="stylesheet" type="text/css" href="'.$file.($item['revision'] == 0 ? '' : '?rev='.$item['revision']).'" />';
                }
            }
        }

        return $result;
    }

    protected function getLoadedJsList($group)
    {
        $group = $this->resolveGroup($group, 'js');

        $result = [];

        foreach($this->loaded as $item)
        {
            if($group != '*')
            {
                if($item['group'] != $group)
                {
                    continue;
                }
            }

            if(isset($item['files']['js']) && is_array($item['files']['js']))
            {
                foreach($item['files']['js'] as $file)
                {
                    $result[] = '<script src="'.$file.($item['revision'] == 0 ? '' : '?rev='.$item['revision']).'"></script>';
                }
            }
        }

        return $result;
    }

    protected function sort()
    {
        array_multisort($this->loaded);
    }
}