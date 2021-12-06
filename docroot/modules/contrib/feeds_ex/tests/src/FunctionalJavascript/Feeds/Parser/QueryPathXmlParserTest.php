<?php

namespace Drupal\Tests\feeds_ex\FunctionalJavascript\Feeds\Parser;

use Drupal\feeds\Entity\Feed;
use Drupal\node\Entity\Node;

/**
 * @coversDefaultClass \Drupal\feeds_ex\Feeds\Parser\QueryPathXmlParser
 * @group feeds_ex
 */
class QueryPathXmlParserTest extends ParserTestBase {

  /**
   * The ID of the parser to test.
   *
   * @var string
   */
  protected $parserId = 'querypathxml';

  /**
   * Tests adding a custom mapping source.
   */
  public function testMapCustomSource() {
    // Set context value.
    $edit = [
      'context' => 'items item',
    ];

    // Add mappings to title and body.
    $this->addMappings($this->feedType->id(), [
      [
        'target' => 'title',
        'map' => [
          'value' => [
            'value' => 'title',
            'machine_name' => 'title_',
          ],
        ],
        'unique' => ['value' => TRUE],
      ],
      [
        'target' => 'body',
        'map' => [
          'value' => [
            'value' => 'description',
            'machine_name' => 'description_',
          ],
        ],
      ],
    ], $edit);

    // Create a feed and import file.
    $edit = [
      'title[0][value]' => 'Feed 1',
      'plugin[fetcher][source]' => $this->resourcesUrl() . '/test.xml',
    ];
    // Save using a dropbutton.
    $this->drupalGet('/feed/add/' . $this->feedType->id());
    $this->submitFormWithDropButton($edit, 'Save');

    // Run import programmatically. Batches don't work well during javascript
    // based tests.
    // @see https://www.drupal.org/project/feeds/issues/2938500#comment-12550186
    $feed = Feed::load(1);
    $feed->import();

    // Assert node values.
    $node1 = Node::load(1);
    $this->assertEquals('I am a title0', $node1->getTitle());
    $this->assertEquals('I am a description0', $node1->body->value);
  }

}
