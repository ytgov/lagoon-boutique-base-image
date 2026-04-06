<?php

namespace Drupal\wxt_library\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LibraryTwig extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters(): array {
    return [
      new TwigFilter('wxtlibrary', [$this, 'getLibraryPath']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'wxt_library.twig_extension';
  }

  /**
   * Generates the full path of the specified theme.
   */
  public static function getLibraryPath(string $theme): string {
    return _wxt_library_get_path($theme, TRUE);
  }

}
