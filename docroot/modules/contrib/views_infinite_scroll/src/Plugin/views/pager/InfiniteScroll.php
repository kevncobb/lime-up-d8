<?php

namespace Drupal\views_infinite_scroll\Plugin\views\pager;

use Drupal\views\Plugin\views\pager\SqlBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Views pager plugin to handle infinite scrolling.
 *
 * @ViewsPager(
 *  id = "infinite_scroll",
 *  title = @Translation("Infinite Scroll"),
 *  short_title = @Translation("Infinite Scroll"),
 *  help = @Translation("A views plugin which provides infinte scroll."),
 *  theme = "views_infinite_scroll_pager"
 * )
 */
class InfiniteScroll extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    $this->updatePageInfo();
    // Replace tokens in the button text.
    $text = $this->options['views_infinite_scroll']['button_text'];
    if (!empty($text) && strpos($text, '@') !== FALSE) {
      $replacements = [
        '@next_page_count' => $this->getNumberItemsLeft(),
        '@total' => (int) $this->getTotalItems(),
      ];
      $this->options['views_infinite_scroll']['button_text'] = strtr($text, $replacements);
    }

    return [
      '#theme' => $this->themeFunctions(),
      '#options' => $this->options['views_infinite_scroll'],
      '#attached' => [
        'library' => ['views_infinite_scroll/views-infinite-scroll'],
      ],
      '#element' => $this->options['id'],
      '#parameters' => $input,
      '#view' => $this->view,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['views_infinite_scroll'] = [
      'contains' => [
        'button_text' => [
          'default' => $this->t('Load More'),
        ],
        'automatically_load_content' => [
          'default' => FALSE,
        ],
        'initially_load_all_pages' => [
          'default' => FALSE,
        ],
      ],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    $action = $this->options['views_infinite_scroll']['automatically_load_content'] ? $this->t('Automatic infinite scroll') : $this->t('Click to load');
    $pages = $this->options['views_infinite_scroll']['initially_load_all_pages'] ? $this->t('Initially load all pages') : $this->t('Initially load one page');
    return $this->formatPlural($this->options['items_per_page'], '@action, @count item', '@action, @count items, @pages', ['@action' => $action, '@count' => $this->options['items_per_page'], '@pages' => $pages]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['tags']['#access'] = FALSE;
    $options = $this->options['views_infinite_scroll'];

    $form['views_infinite_scroll'] = [
      '#title' => $this->t('Infinite Scroll Options'),
      '#description' => $this->t('Note: The infinite scroll option overrides and requires the <em>Use AJAX</em> setting for this views display.'),
      '#type' => 'details',
      '#open' => TRUE,
      '#tree' => TRUE,
      '#input' => TRUE,
      '#weight' => -100,
      'button_text' => [
        '#type' => 'textfield',
        '#title' => $this->t('Button Text'),
        '#default_value' => $options['button_text'],
        '#description' => [
          '#theme' => 'item_list',
          '#items' => [
            '@next_page_count -- the next page record count',
            '@total -- the total amount of results returned from the view',
          ],
          '#prefix' => $this->t('The following tokens are supported:'),
        ],
      ],
      'automatically_load_content' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Automatically Load Content'),
        '#description' => $this->t('Automatically load subsequent pages as the user scrolls.'),
        '#default_value' => $options['automatically_load_content'],
      ],
      'initially_load_all_pages' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Initially load all pages up to the requested page'),
        '#description' => $this->t('When initially loading a page beyond the first, this option will load all pages up to the requested page instead of just the requested page. So, if you have the pager set to 10 items per page, and you load the page with ?page=2 in the url, you will get page 0, 1 and 2 loaded for a total of 30 items. <em>Note that this could cause some long page load times when loading many pages.</em>'),
        '#default_value' => $options['initially_load_all_pages'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  function query() {
    // Run the pant method which is sufficient if we're on the first page.
    parent::query();
    // If configured, for pages beyond the first, we want to show all items up
    // to the current page.
    if ($this->options['views_infinite_scroll']['initially_load_all_pages'] && !\Drupal::request()->isXmlHttpRequest() && $this->current_page > 0) {
      $limit = ($this->current_page + 1) * $this->options['items_per_page'];
      $offset = $this->options['offset'];
          $this->view->query->setLimit($limit);
      $this->view->query->setOffset($offset);
    }
  }

  /**
   * Returns the number of items in the next page.
   *
   * @return int
   *   The number of items in the next page.
   */
  protected function getNumberItemsLeft() {
    $items_per_page = (int) $this->view->getItemsPerPage();
    $total = (int) $this->getTotalItems();
    $current_page = (int) $this->getCurrentPage() + 1;

    // Default to the pager amount.
    $next_page_count = $items_per_page;
    // Calculate the remaining items if we are at the 2nd to last page.
    if ($current_page >= ceil($total / $items_per_page) - 1) {
      $next_page_count = $total - ($current_page * $items_per_page);
      return $next_page_count;
    }
    return $next_page_count;
  }

}
