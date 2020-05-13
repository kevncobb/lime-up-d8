<?php

namespace Drupal\Tests\views_bulk_operations\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * @coversDefaultClass \Drupal\views_bulk_operations\Plugin\views\field\ViewsBulkOperationsBulkForm
 * @group views_bulk_operations
 */
class ViewsBulkOperationsBulkFormTest extends BrowserTestBase {

  const TEST_NODE_COUNT = 15;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'views',
    'views_bulk_operations',
    'views_bulk_operations_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create some nodes for testing.
    $this->drupalCreateContentType(['type' => 'page']);

    $this->testNodes = [];
    $time = $this->container->get('datetime.time')->getRequestTime();
    for ($i = 0; $i < self::TEST_NODE_COUNT; $i++) {
      // Ensure nodes are sorted in the same order they are inserted in the
      // array.
      $time -= $i;
      $this->testNodes[] = $this->drupalCreateNode([
        'type' => 'page',
        'title' => 'Title ' . $i,
        'sticky' => FALSE,
        'created' => $time,
        'changed' => $time,
      ]);
    }

  }

  /**
   * Helper function to test a batch process.
   *
   * After checking if we're on a Batch API page,
   * the iterations are executed, the finished page is opened
   * and browser redirects to the final destination.
   *
   * NOTE: As of Drupal 8.4, functional test
   * automatically redirects user through all Batch API pages,
   * so this function is not longer needed.
   */
  protected function assertBatchProcess() {
    // Get the current batch ID.
    $current_url = $this->getUrl();
    $q = substr($current_url, strrpos($current_url, '/') + 1);
    $this->assertEquals('batch?', substr($q, 0, 6), 'We are on a Batch API page.');

    preg_match('#id=([0-9]+)#', $q, $matches);
    $batch_id = $matches[1];

    // Proceed with the operations.
    // Assumption: all operations will be completed within a single request.
    // TODO: modify code to include an option when the assumption is false.
    do {
      $this->drupalGet('batch', [
        'query' => [
          'id' => $batch_id,
          'op' => 'do_nojs',
        ],
      ]);
    } while (FALSE);

    // Get the finished page.
    $this->drupalGet('batch', [
      'query' => [
        'id' => $batch_id,
        'op' => 'finished',
      ],
    ]);
  }

  /**
   * Tests the VBO bulk form with simple test action.
   */
  public function testViewsBulkOperationsBulkFormSimple() {

    $assertSession = $this->assertSession();

    $this->drupalGet('views-bulk-operations-test');

    // Test that the views edit header appears first.
    $first_form_element = $this->xpath('//form/div[1][@id = :id]', [':id' => 'edit-header']);
    $this->assertNotEmpty($first_form_element, 'The views form edit header appears first.');

    // Make sure a checkbox appears on all rows.
    $edit = [];
    for ($i = 0; $i < 4; $i++) {
      $assertSession->fieldExists('edit-views-bulk-operations-bulk-form-' . $i, NULL, new FormattableMarkup('The checkbox on row @row appears.', ['@row' => $i]));
    }

    // The advanced action should not be shown on the form - no permission.
    $this->assertEmpty($this->cssSelect('input[value=views_bulk_operations_advanced_test_action]'), t('Advanced action is not selectable.'));

    // Log in as a user with 'edit any page content' permission
    // to have access to perform the test operation.
    $admin_user = $this->drupalCreateUser(['edit any page content']);
    $this->drupalLogin($admin_user);

    // Execute the simple test action.
    $edit = [];
    $selected = [0, 2, 3];
    foreach ($selected as $index) {
      $edit["views_bulk_operations_bulk_form[$index]"] = TRUE;
    }

    // Tests: actions as buttons, label override.
    $this->drupalPostForm('views-bulk-operations-test', $edit, t('Simple test action'));

    $testViewConfig = \Drupal::service('config.factory')->get('views.view.views_bulk_operations_test');
    $configData = $testViewConfig->getRawData();
    $preconfig_setting = $configData['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['selected_actions'][0]['preconfiguration']['preconfig'];

    foreach ($selected as $index) {
      $assertSession->pageTextContains(
        sprintf('Test action (preconfig: %s, label: %s)',
          $preconfig_setting,
          $this->testNodes[$index]->label()
        ),
        sprintf('Action has been executed on node "%s".',
          $this->testNodes[$index]->label()
        )
      );
    }

    // Test the select all functionality.
    $edit = [
      'select_all' => 1,
    ];
    // With the exclude mode, we also have to select all rows of the
    // view, otherwise those will be treated as excluded. In the UI
    // this is handled by JS.
    foreach ([0, 1, 2, 3] as $index) {
      $edit["views_bulk_operations_bulk_form[$index]"] = TRUE;
    }

    $this->drupalPostForm(NULL, $edit, t('Simple test action'));

    $assertSession->pageTextContains(
      sprintf('Action processing results: Test (%d).', self::TEST_NODE_COUNT),
      sprintf('Action has been executed on %d nodes.', self::TEST_NODE_COUNT)
    );

  }

  /**
   * More advanced test.
   *
   * Uses the ViewsBulkOperationsAdvancedTestAction.
   */
  public function testViewsBulkOperationsBulkFormAdvanced() {

    $assertSession = $this->assertSession();

    // Log in as a user with 'edit any page content' permission
    // to have access to perform the test operation.
    $admin_user = $this->drupalCreateUser(['edit any page content', 'execute advanced test action']);
    $this->drupalLogin($admin_user);

    // First execute the simple action to test
    // the ViewsBulkOperationsController class.
    $edit = [
      'action' => 0,
    ];
    $selected = [0, 2];
    foreach ($selected as $index) {
      $edit["views_bulk_operations_bulk_form[$index]"] = TRUE;
    }
    $this->drupalPostForm('views-bulk-operations-test-advanced', $edit, t('Apply to selected items'));

    $assertSession->pageTextContains(
      sprintf('Action processing results: Test (%d).', count($selected)),
      sprintf('Action has been executed on %d nodes.', count($selected))
    );

    // Execute the advanced test action.
    $edit = [
      'action' => 1,
    ];
    $selected = [0, 1, 3];
    foreach ($selected as $index) {
      $edit["views_bulk_operations_bulk_form[$index]"] = TRUE;
    }
    $this->drupalPostForm('views-bulk-operations-test-advanced', $edit, t('Apply to selected items'));

    // Check if the configuration form is open and contains the
    // test_config field.
    $assertSession->fieldExists('edit-test-config', NULL, 'The configuration field appears.');

    // Check if the configuration form contains selected entity labels.
    // NOTE: The view pager has an offset set on this view, so checkbox
    // indexes are not equal to test nodes array keys. Hence the $index + 1.
    foreach ($selected as $index) {
      $assertSession->pageTextContains($this->testNodes[$index + 1]->label());
    }

    $config_value = 'test value';
    $edit = [
      'test_config' => $config_value,
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply'));

    // Execute action by posting the confirmation form
    // (also tests if the submit button exists on the page).
    $this->drupalPostForm(NULL, [], t('Execute action'));

    // If all went well and Batch API did its job,
    // the next page should display results.
    $testViewConfig = \Drupal::service('config.factory')->get('views.view.views_bulk_operations_test_advanced');
    $configData = $testViewConfig->getRawData();
    $preconfig_setting = $configData['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['selected_actions'][1]['preconfiguration']['test_preconfig'];

    // NOTE: The view pager has an offset set on this view, so checkbox
    // indexes are not equal to test nodes array keys. Hence the $index + 1.
    foreach ($selected as $index) {
      $assertSession->pageTextContains(sprintf('Test action (preconfig: %s, config: %s, label: %s)',
        $preconfig_setting,
        $config_value,
        $this->testNodes[$index + 1]->label()
      ));
    }

    // Test the exclude functionality with batching and entity
    // property changes affecting view query results.
    $edit = [
      'action' => 1,
      'select_all' => 1,
    ];
    // Let's leave two checkboxes unchecked to test the exclude mode.
    foreach ([0, 2] as $index) {
      $edit["views_bulk_operations_bulk_form[$index]"] = TRUE;
    }
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->drupalPostForm(NULL, ['test_config' => 'unpublish'], t('Apply'));
    $this->drupalPostForm(NULL, [], t('Execute action'));
    // Again, take offset into account (-1), also take 2 excluded
    // rows into account (-2).
    $assertSession->pageTextContains(
      sprintf('Action processing results: Test (%d).', (count($this->testNodes) - 3)),
      sprintf('Action has been executed on all %d nodes.', (count($this->testNodes) - 3))
    );

    $this->assertNotEmpty((count($this->cssSelect('table.vbo-table tbody tr')) === 2), "The view shows only excluded results.");
  }

  /**
   * View and context passing test.
   *
   * Uses the ViewsBulkOperationsPassTestAction.
   */
  public function testViewsBulkOperationsBulkFormPassing() {

    $assertSession = $this->assertSession();

    // Log in as a user with 'administer content' permission
    // to have access to perform the test operation.
    $admin_user = $this->drupalCreateUser(['bypass node access']);
    $this->drupalLogin($admin_user);

    // Test with all selected and specific selection, with batch
    // size greater than items per page and lower than items per page,
    // using Batch API process and without it.
    $cases = [
      ['batch' => FALSE, 'selection' => TRUE, 'page' => 1],
      ['batch' => FALSE, 'selection' => FALSE],
      ['batch' => TRUE, 'batch_size' => 3, 'selection' => TRUE, 'page' => 1],
      ['batch' => TRUE, 'batch_size' => 7, 'selection' => TRUE],
      ['batch' => TRUE, 'batch_size' => 3, 'selection' => FALSE],
      ['batch' => TRUE, 'batch_size' => 7, 'selection' => FALSE],
    ];

    // Custom selection.
    $selected = [0, 1, 3, 4];

    $testViewConfig = \Drupal::service('config.factory')->getEditable('views.view.views_bulk_operations_test_advanced');
    $configData = $testViewConfig->getRawData();
    $items_per_page = 5;

    foreach ($cases as $case) {
      $items_per_page++;

      // Populate form values.
      $edit = [
        'action' => 2,
      ];
      if ($case['selection']) {
        foreach ($selected as $index) {
          $edit["views_bulk_operations_bulk_form[$index]"] = TRUE;
        }
      }
      else {
        $edit['select_all'] = 1;
        // So we don't cause exclude mode.
        for ($i = 0; $i < $items_per_page; $i++) {
          $edit["views_bulk_operations_bulk_form[$i]"] = TRUE;
        }
      }

      // Update test view configuration.
      $configData['display']['default']['display_options']['pager']['options']['items_per_page'] = $items_per_page;
      $configData['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['batch'] = $case['batch'];
      if (isset($case['batch_size'])) {
        $configData['display']['default']['display_options']['fields']['views_bulk_operations_bulk_form']['batch_size'] = $case['batch_size'];
      }
      $testViewConfig->setData($configData);
      $testViewConfig->save();

      $options = [];
      if (!empty($case['page'])) {
        $options['query'] = ['page' => $case['page']];
      }

      $this->drupalGet('views-bulk-operations-test-advanced', $options);
      $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

      // On batch-enabled processes check if provided context data is correct.
      if ($case['batch']) {
        if ($case['selection']) {
          $total = count($selected);
        }
        else {
          // Again, include offset.
          $total = count($this->testNodes) - 1;
        }
        $n_batches = ceil($total / $case['batch_size']);

        for ($i = 0; $i < $n_batches; $i++) {
          $processed = $i * $case['batch_size'];
          $assertSession->pageTextContains(sprintf(
            'Processed %s of %s.',
            $processed,
            $total
          ), 'The correct processed info message appears.');
        }
      }

      // Passed view integrity check.
      $assertSession->pageTextContains('Passed view results match the entity queue.');
    }

  }

}
