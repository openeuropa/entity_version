<?php

declare(strict_types = 1);

namespace Drupal\entity_version_workflows\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber to alter routes necessary for the module.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Add our own form controller to the revision revert route.
    if ($revert_form_route = $collection->get('node.revision_revert_confirm')) {
      $revert_form_route->setDefault('_form', '\Drupal\entity_version_workflows\Form\NodeRevisionRevertForm');
      $revert_form_route->compile();
    }
  }

}
