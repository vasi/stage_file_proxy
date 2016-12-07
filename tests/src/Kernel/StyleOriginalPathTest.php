<?php

namespace Drupal\Tests\stage_file_proxy\Kernel;

use Drupal\image\ImageStyleInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\stage_file_proxy\FetchManager;

/**
 * Tests finding the original path of a file.
 *
 * @group stage_file_proxy
 */
class StyleOriginalPathTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system', 'file', 'image', 'stage_file_proxy',
  ];

  /**
   * The fetch manager.
   *
   * @var \Drupal\stage_file_proxy\FetchManager
   */
  protected $fetchManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['system']);
    $this->fetchManager = \Drupal::service('stage_file_proxy.fetch_manager');
  }

  /**
   * Test styleOriginalPath for URIs that are not styled images.
   */
  public function testUnstyled() {
    $this->assertEquals(
      FALSE,
      $this->fetchManager->styleOriginalPath('foo.png', TRUE),
      'Unstyled URI with $style_only yields FALSE'
    );

    // If we allow unstyled URIs, an unstyled URI yields a path.
    // Is this necessary? This code path is unused!
    $this->assertEquals(
      'public://foo.png',
      $this->fetchManager->styleOriginalPath('foo.png', FALSE),
      'Unstyled URI without $style_only yields a path with default scheme'
    );

    // If a path has a scheme, use that scheme.
    // Is this necessary? This code path is unused!
    $this->assertEquals(
      'test://foo.png',
      $this->fetchManager->styleOriginalPath('test://foo.png', FALSE),
      'Untyled URI with scheme retains its scheme'
    );
  }

  /**
   * Test styleOriginalPath for URIs that are styled images.
   */
  public function testStyled() {
    $style_storage = \Drupal::entityTypeManager()->getStorage('image_style');

    // Create some styles.
    /** @var ImageStyleInterface[] $styles */
    $styles = [];
    $styles['basic'] = $style_storage->create(['name' => 'basic']);
    foreach ($styles as $style) {
      $style->save();
    }

    // Test each style.
    $original = 'public://foo/bar.png';
    foreach ($styles as $name => $style) {
      // Get the actual styled URI for this style.
      $uri = $style->buildUri($original);
      $uri = preg_replace(',^public://,', '', $uri);

      // See if we turn it back into the proper original.
      $this->assertEquals(
        $original,
        $this->fetchManager->styleOriginalPath($uri, TRUE),
        "URI with style '$name'' yields original file"
      );
    }

    // Test a nonexistent style.
    $fake = 'sites/default/files/styles/fake/public/foo/bar.png';
    // The output is irrelevant, but at least it shouldn't crash.
    $this->fetchManager->styleOriginalPath($fake);
  }

}
