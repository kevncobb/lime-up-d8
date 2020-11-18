<?php

namespace Drupal\responsive_preview;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeForm;
use Drupal\node\NodeInterface;

/**
 * Class ResponsivePreviewHandler.
 *
 * @package Drupal\responsive_preview
 */
class ResponsivePreviewHandler {

  /**
   * Determine if responsive preview should be available on page.
   *
   * @return bool
   *   TRUE if page is node add page.
   */
  public static function isAvailableAjaxPreview() {
    // If we are on an edit-form, try to resolve the canonical url.
    $routeMatch = \Drupal::service('current_route_match');
    if ($routeMatch->getRouteName() === 'node.add') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Handling of form alter, for responsive preview.
   *
   * Request to this method is piped from module related hook_form_alter().
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param string $form_id
   *   Form ID.
   */
  public static function handleFormAlter(array &$form, FormStateInterface $form_state, $form_id) {
    if (!$form_state->getFormObject() instanceof NodeForm) {
      return;
    }

    /** @var \Drupal\Core\Entity\Entity $entity */
    $node = $form_state->getFormObject()->getEntity();

    if ($node instanceof NodeInterface) {
      $preview_mode = $node->type->entity->getPreviewMode();

      $form['ajax_responsive_preview'] = [
        '#type' => 'hidden',
        '#name' => 'ajax_responsive_preview',
        '#id' => 'ajax_responsive_preview',
        '#attributes' => ['id' => 'ajax_responsive_preview'],
        '#access' => $preview_mode != DRUPAL_DISABLED && ($node->access('create') || $node->access('update')),
        '#submit' => $form['actions']['preview']['#submit'],
        '#executes_submit_callback' => TRUE,
        '#ajax' => [
          'callback' => [
            __CLASS__,
            'handleAjaxDevicePreview',
          ],
          'event' => 'show-responsive-preview',
          'progress' => [
            'type' => 'fullscreen',
          ],
        ],
        '#attached' => [
          'drupalSettings' => [
            'responsive_preview' => [
              'ajax_responsive_preview' => '#ajax_responsive_preview',
            ],
          ],
        ],
      ];
    }
  }

  /**
   * Handles response for AJAX request.
   *
   * @param array $form
   *   From array object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   Returns AJAX response object.
   */
  public static function handleAjaxDevicePreview(array $form, FormStateInterface $form_state) {
    // If there are no errors and everything is fine, then result for opening
    // responsive preview will be generated.
    $ajax = new AjaxResponse();

    // Handling of errors is a bit tricky and here is workaround with triggering
    // of normal preview functionality.
    if (count($form_state->getErrors()) > 0) {
      // Clearing error messages, because they will be generated by clicking on
      // "Preview" button.
      \Drupal::messenger()->deleteAll();

      // Triggering click on "Preview" button, in order to get error messages
      // properly displayed in UI, since it's not possible to propagate them
      // nicely over AJAX response.
      $ajax->addCommand(
        new InvokeCommand('#edit-preview', 'click')
      );
    }
    elseif (($triggering_element = $form_state->getTriggeringElement()) && $triggering_element['#name'] === 'ajax_responsive_preview') {
      $form_state->disableRedirect(FALSE);
      $redirectUrl = $form_state->getRedirect();
      $form_state->disableRedirect();

      $ajax->addCommand(
        new SettingsCommand(
          [
            'responsive_preview' => [
              'url' => ltrim($redirectUrl->toString(), '/'),
            ],
          ],
          TRUE
        ),
        TRUE
      );

      $deviceId = $form_state->getValue($triggering_element['#name']);
      $ajax->addCommand(
        new InvokeCommand("[data-responsive-preview-name='{$deviceId}']", 'trigger', ['open-preview'])
      );
    }

    return $ajax;
  }

}
