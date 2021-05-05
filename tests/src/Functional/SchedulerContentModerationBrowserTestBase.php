<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\scheduler\Traits\SchedulerMediaSetupTrait;

/**
 * Base class from which all functional browser tests can be extended.
 *
 * @group scheduler_content_moderation_integration
 */
abstract class SchedulerContentModerationBrowserTestBase extends BrowserTestBase {

  use ContentModerationTestTrait;
  use SchedulerMediaSetupTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'scheduler_content_moderation_integration',
    'content_moderation',
    'media',
  ];

  /**
   * The moderation workflow.
   *
   * @var \Drupal\workflows\Entity\Workflow
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ])
      ->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Use SchedulerMediaSetupTrait function for ease of creating Media a type.
    $this->createMediaType('audio_file', [
      'id' => 'soundtrack',
      'label' => 'Sound track',
    ])
      ->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();

    // Set the media file attachments to be optional not required, to simplify
    // editing and saving media entities during tests.
    \Drupal::configFactory()->getEditable('field.field.media.soundtrack.field_media_audio_file')
      ->set('required', FALSE)
      ->save(TRUE);

    $this->workflow = $this->createEditorialWorkflow();
    $this->workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $this->workflow->getTypePlugin()->addEntityTypeAndBundle('media', 'soundtrack');
    $this->workflow->save();

    // Define mediaStorage for use in SchedulerMediaSetupTrait functions.
    /** @var MediaStorageInterface $mediaStorage */
    $this->mediaStorage = $this->container->get('entity_type.manager')->getStorage('media');

    // Create user with full permission to schedule node content and use all
    // editorial transitions.
    $this->schedulerUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'edit any page content',
      'schedule publishing of nodes',
      'view latest version',
      'view any unpublished content',
      'access content overview',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'use editorial transition archive',
    ]);

    // Create a restricted user without permission to schedule node content or
    // use the publish and archive transitions.
    $this->restrictedUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'edit own page content',
      'view latest version',
      'view any unpublished content',
      'access content overview',
      'use editorial transition create_new_draft',
    ]);

    // Create media user with full permission to schedule media content and
    // use all editorial transitions.
    $this->schedulerMediaUser = $this->drupalCreateUser([
      'create soundtrack media',
      'edit any soundtrack media',
      'schedule publishing of media',
      'view latest version',
      'access media overview',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'use editorial transition archive',
    ]);

    // Create a restricted media user without permission to schedule media or
    // use the publish and archive transitions.
    $this->restrictedMediaUser = $this->drupalCreateUser([
      'create soundtrack media',
      'edit any soundtrack media',
      'view latest version',
      'access media overview',
      'use editorial transition create_new_draft',
    ]);

  }

  /**
   * Returns the stored entity type object from a type id and bundle id.
   *
   * @param string $entityTypeId
   *   The machine name of the entity type, for example 'node' or 'media'.
   * @param string $bundle
   *   The machine name of the bundle, for example 'page' or 'soundtrack'.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The stored entity type object.
   */
  public function entityTypeObject(string $entityTypeId, string $bundle) {
    $entityTypeManager = $this->container->get('entity_type.manager');
    if ($definition = $entityTypeManager->getDefinition($entityTypeId)) {
      if ($bundle_entity_type = $definition->getBundleEntityType()) {
        if ($entityType = $entityTypeManager->getStorage($bundle_entity_type)->load($bundle)) {
          return $entityType;
        }
      }
    }
    // Show the incorrect parameter values.
    throw new \Exception(sprintf('Invalid entityTypeId "%s" and bundle "%s" combination passed to entityTypeObject()', $entityTypeId, $bundle));
  }

  /**
   * Test data for node and media test entity types.
   *
   * @return array
   *   Each array item has the values: [entity type id, bundle id].
   */
  public function dataEntityTypes() {
    $data = [
      0 => ['node', 'page'],
      1 => ['media', 'soundtrack'],
    ];
    return $data;
  }

}
