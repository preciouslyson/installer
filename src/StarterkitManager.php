<?php

namespace Preciouslyson\MachinjiriInstaller;
use \Exception;
use Preciouslyson\MachinjiriInstaller\StarterKits\DefaultKit;
use Preciouslyson\MachinjiriInstaller\StarterKits\Blog;
final class StarterkitManager
{
  public function __construct (private ?string $projectDir = null) {}
  public function install(string $starterKit, callable $write, array $options = []) 
  {
    if (!is_callable($write)) {
      throw new Exception('Could not install starter kit: ' . $starterKit);
    }
    switch (strtolower($starterKit)) {
      case 'default':
        DefaultKit::getOptions($options);
        foreach (DefaultKit::files() as $key => $file) {
          $write($this->projectDir . $file['file'], $file['template']);
        }
        break;
      case 'blog':
        foreach (Blog::files() as $key => $file) {
          $write($this->projectDir . $file['file'], $file['template']);
        }
        break;
      default:
        throw new Exception('Unknown starter kit: ' . $starterKit);
    }
  }
  
  public static function startKits(): array 
  {
    return [
      'default' => 'Default Starter kit',
      'blog' => 'Blog Starter kit',
    ];
  }
}