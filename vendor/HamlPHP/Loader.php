<?php

require_once 'Storage/Storage.php';

interface Loader
{
  /**
   * Loads content from the storage.
   *
   * @param Storage $storage
   * @param mixed $fileName
   *
   * @return string The content from the storage
   */
  public function load(Storage $storage, $fileName);
}