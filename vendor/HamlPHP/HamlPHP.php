<?php

require_once 'Storage/Storage.php';
require_once 'Compiler.php';
require_once 'ContentEvaluator/DefaultContentEvaluator.php';
require_once 'Loader.php';

class HamlPHP implements \Loader
{
  private $_compiler = null;
  private $_storage = null;
  private $_contentEvaluator = null;
  private $_nodeFactory = null;
  private $_filterContainer = null;
  private $_loader = null;
  private $_cacheEnabled = true;

  // Placeholder until config gets properly implemented
  public static $Config = array(
  	'escape_html' => false
  );

  public function __construct(Storage $storage)
  {
    $this->_compiler = $this->getCompiler();
    $this->_storage = $storage;

    if ($this->_storage instanceof ContentEvaluator) {
      $this->setContentEvaluator($this->_storage);
    } else {
      $this->setContentEvaluator(new DefaultContentEvaluator());
    }
  }

  /**
   * Sets a content evaluator.
   *
   * @param ContentEvaluator $contentEvaluator
   */
  public function setContentEvaluator(ContentEvaluator $contentEvaluator)
  {
    $this->_contentEvaluator = $contentEvaluator;
  }

  /**
   * Sets a filter container and updates the node factory to use it.
   *
   * @param FilterContainer $container
   */
  public function setFilterContainer(FilterContainer $container)
  {
    $this->_filterContainer = $container;
    $this->getNodeFactory()->setFilterContainer($this->getFilterContainer());
  }

  /**
   * Returns a filter container object. Initializes the filter container with
   * default filters if it's null.
   *
   * @return FilterContainer
   */
  public function getFilterContainer()
  {
    if ($this->_filterContainer === null) {
      $filterContainer = new FilterContainer();
      $filterContainer->addFilter(new CssFilter());
      $filterContainer->addFilter(new PlainFilter());
      $filterContainer->addFilter(new JavascriptFilter());
      $filterContainer->addFilter(new PhpFilter());

      $this->_filterContainer = $filterContainer;
    }

    return $this->_filterContainer;
  }

  /**
   * Sets a node factory.
   *
   * @param NodeFactory $factory
   */
  public function setNodeFactory(NodeFactory $factory)
  {
    $this->_nodeFactory = $factory;
    $this->getNodeFactory()->setFilterContainer($this->getFilterContainer());
  }

  /**
   * Returns a node factory object.
   *
   * @return NodeFactory
   */
  public function getNodeFactory()
  {
    if ($this->_nodeFactory === null) {
      $this->setNodeFactory(new NodeFactory());
    }

    return $this->_nodeFactory;
  }

  /**
   * Sets a compiler.
   *
   * @param Compiler $compiler
   */
  public function setCompiler(Compiler $compiler)
  {
    $this->_compiler = $compiler;
  }

  /**
   * Returns a compiler object.
   *
   * @return Compiler
   */
  public function getCompiler()
  {
    if ($this->_compiler === null) {
      $this->_compiler = new Compiler($this);
    }

    return $this->_compiler;
  }

  /**
   * Returns a loader instance.
   *
   * @return Loader The Loader interface instance
   */
  public function getLoader()
  {
    if (null === $this->_loader) {
      $this->_loader = $this;
    }

    return $this->_loader;
  }

  /**
   * Sets a Loader instance.
   *
   * @param Loader $loader
   */
  public function setLoader(Loader $loader)
  {
    $this->_loader = $loader;
  }

  /**
   * Enables caching.
   */
  public function enableCache()
  {
    $this->_cacheEnabled = true;
  }

  /**
   * Disables caching.
   */
  public function disableCache()
  {
    $this->_cacheEnabled = false;
  }

  /**
   * Returns true if caching is enabled.
   *
   * @return bool
   */
  public function isCacheEnabled()
  {
    return $this->_cacheEnabled;
  }

  /**
   * Parses a haml file and returns a cached path to the file.
   *
   * @param string $fileName
   */
  public function parseFile($fileName, array $templateVars = array())
  {
    
//    \Debug::dump($templateVars);die;    
    
    $loader = $this->getLoader();
    $content = $loader->load($this->_storage, $fileName);
    
    return $this->_contentEvaluator->evaluate(
        $content, $templateVars, $this->generateFileId($fileName));
  }

  public function load(Storage $storage, $fileName)
  {
    $fileId = $this->generateFileId($fileName);

    if ($storage === null) {
      throw new Exception('Storage not set');
    }

    if ($this->isCacheEnabled()
        && $storage->isFresh($fileId)) {
          
      return $storage->fetch($fileId);
    }

    // file is not fresh, so compile and cache it
    $storage->cache($fileId, $this->getCompiler()->parseFile($fileName));

    return $storage->fetch($fileId);
  }

  /**
   * Returns content from a storage
   *
   * @deprecated Use the load method of the Loader interface instead.
   * @param string $fileName
   * @return string
   */
  public function getContentFromStorage($fileName)
  {
  	return $this->getLoader()->load($this->_storage, $fileName);
  }

  private function generateFileId($filename)
  {
  	return str_replace(array(':','/','\\'), '_', ltrim($filename, '/\\'));
  }
}
