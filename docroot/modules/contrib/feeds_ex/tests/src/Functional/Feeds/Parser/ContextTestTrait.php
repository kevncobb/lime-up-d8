<?php

namespace Drupal\Tests\feeds_ex\Functional\Feeds\Parser;

/**
 * Trait for testing the context field on the mapping form.
 */
trait ContextTestTrait {

  /**
   * Tests setting a context on the mapping form.
   *
   * @covers ::mappingFormAlter
   * @covers ::mappingFormValidate
   * @covers ::mappingFormSubmit
   *
   * @dataProvider dataProviderValidContext
   */
  public function testSetContext($context, $expected_context = NULL) {
    if (is_null($expected_context)) {
      $expected_context = $context;
    }

    $edit = [
      'context' => $context,
    ];

    $this->drupalPostForm('/admin/structure/feeds/manage/' . $this->feedType->id() . '/mapping', $edit, 'Save');

    // Assert that the context was saved for the XML parser.
    $feed_type = $this->reloadEntity($this->feedType);
    $config = $feed_type->getParser()->getConfiguration();
    $this->assertEquals($expected_context, $config['context']['value']);
  }

  /**
   * Data provider for testSetContext().
   */
  abstract public function dataProviderValidContext();

  /**
   * Tests setting an invalid context on the mapping form.
   *
   * @covers ::mappingFormAlter
   * @covers ::mappingFormValidate
   *
   * @dataProvider dataProviderInvalidContext
   */
  public function testSetInvalidContext($context, $expected_error, $expected_context = '') {
    $edit = [
      'context' => $context,
    ];

    $this->drupalPostForm('/admin/structure/feeds/manage/' . $this->feedType->id() . '/mapping', $edit, 'Save');
    $this->assertText($expected_error);

    // Assert that the context was *not* saved for the parser.
    $feed_type = $this->reloadEntity($this->feedType);
    $config = $feed_type->getParser()->getConfiguration();
    $this->assertEquals($expected_context, $config['context']['value']);
  }

  /**
   * Data provider for testSetInvalidContext().
   */
  abstract public function dataProviderInvalidContext();

  /**
   * Does a basic mapping test.
   */
  public function testMapping() {
    // First, set context.
    $data = $this->dataProviderValidContext();
    $contexts = reset($data);
    $context = reset($contexts);

    $edit = [
      'context' => $context,
    ];

    $this->drupalPostForm('/admin/structure/feeds/manage/' . $this->feedType->id() . '/mapping', $edit, 'Save');

    // Now set mapping.
    $this->doMappingTest();
  }

}
