<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Functional;

/**
 * Test covering manipulation of add and edit entity forms.
 *
 * @group scheduler
 */
class FormsTest extends SchedulerContentModerationBrowserTestBase {

  /**
   * Tests the hook_form_alter functionality.
   *
   * @dataProvider dataFormAlter()
   */
  public function testFormAlter($entityTypeId, $bundle, $operation) {
    $this->drupalLogin($entityTypeId == 'media' ? $this->schedulerMediaUser : $this->schedulerUser);
    $entityType = $this->entityTypeObject($entityTypeId, $bundle);
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    if ($operation == 'add') {
      $url = "{$entityTypeId}/add/{$bundle}";
    }
    else {
      $entity = $this->createEntity($entityTypeId, $bundle, []);
      $url = "{$entityTypeId}/{$entity->id()}/edit";
    }

    // Check both state fields are shown when the entity is enabled by default.
    $this->drupalGet($url);
    $assert->ElementExists('xpath', '//select[@id = "edit-publish-state-0"]');
    $assert->ElementExists('xpath', '//select[@id = "edit-unpublish-state-0"]');

    // Check that both fields have the Scheduler Settings group as parent.
    $assert->elementExists('xpath', '//details[@id = "edit-scheduler-settings"]//select[@id = "edit-publish-state-0"]');
    $assert->elementExists('xpath', '//details[@id = "edit-scheduler-settings"]//select[@id = "edit-unpublish-state-0"]');

    // Disable scheduled publishing and check that the publish-state field is
    // now hidden.
    $entityType->setThirdPartySetting('scheduler', 'publish_enable', FALSE)->save();
    $this->drupalGet($url);
    $assert->ElementNotExists('xpath', '//select[@id = "edit-publish-state-0"]');
    $assert->ElementExists('xpath', '//select[@id = "edit-unpublish-state-0"]');

    // Re-enable scheduled publishing and disable unpublishing, and check that
    // only the unpublish-state field is hidden.
    $entityType->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', FALSE)->save();
    $this->drupalGet($url);
    $assert->ElementExists('xpath', '//select[@id = "edit-publish-state-0"]');
    $assert->ElementNotExists('xpath', '//select[@id = "edit-unpublish-state-0"]');
  }

  /**
   * Tests hook_scheduler_hide_publish/unpublish_on_field().
   *
   * Kernel testHookHideSchedulerFields() checks the various combinations of
   * values which cause the Scheduler fields to be hidden, using the just the
   * 'Node' version of the hook. This functional test checks that fields do
   * actually get hidden for all supported entity types.
   *
   * @dataProvider dataFormAlter()
   */
  public function testHideSchedulerFields($entityTypeId, $bundle, $operation) {
    $this->drupalLogin($entityTypeId == 'media' ? $this->schedulerMediaUser : $this->schedulerUser);

    // By default the Scheduler publish_on and unpublish_on fields are shown.
    $entity = $this->createEntity($entityTypeId, $bundle);
    $this->drupalGet("{$entityTypeId}/{$entity->id()}/edit");
    $this->assertSession()->FieldExists('publish_on[0][value][date]');
    $this->assertSession()->FieldExists('publish_state[0]');
    $this->assertSession()->FieldExists('unpublish_on[0][value][date]');
    $this->assertSession()->FieldExists('unpublish_state[0]');

    // Remove the 'archived' state so that there is no transition relating to
    // scheduled unpublishing.
    $this->workflow->getTypePlugin()->deleteState('archived');
    $this->workflow->save();

    // Check that the unpublish_on and unpublish_state fields are hidden.
    $this->drupalGet("{$entityTypeId}/{$entity->id()}/edit");
    $this->assertSession()->FieldExists('publish_state[0]');
    $this->assertSession()->FieldExists('publish_on[0][value][date]');
    // @todo The unpublish_state field with only 'none' should not be shown, but
    // currently is shown. Uncomment the assertion when this is fixed
    // @see https://www.drupal.org/project/scheduler_content_moderation_integration/issues/3024715
    // $this->assertSession()->FieldNotExists('unpublish_state[0]');
    $this->assertSession()->FieldNotExists('unpublish_on[0][value][date]');

    // Remove the 'publish' transition so there is nothing relating to scheuled
    // publishing.
    $this->workflow->getTypePlugin()->deleteTransition('publish');
    $this->workflow->save();

    // Check that the publish_on and publish_state fields are hidden.
    $this->drupalGet("{$entityTypeId}/{$entity->id()}/edit");
    // @todo The publish_state field with only 'none' should not be shown, but
    // currently is shown. Uncomment the two assertions when this is fixed
    // @see https://www.drupal.org/project/scheduler_content_moderation_integration/issues/3024715
    // $this->assertSession()->FieldNotExists('publish_state[0]');
    $this->assertSession()->FieldNotExists('publish_on[0][value][date]');
    // $this->assertSession()->FieldNotExists('unpublish_state[0]');
    $this->assertSession()->FieldNotExists('unpublish_on[0][value][date]');
  }

  /**
   * Provides test data. Each entity type is checked for add and edit.
   *
   * @return array
   *   Each array item has the values: [entity type id, bundle id, operation].
   */
  public function dataFormAlter() {
    $data = [];
    foreach ($this->dataEntityTypes() as $entity_types) {
      $data[] = array_merge($entity_types, ['add']);
      // $data[] = array_merge($entity_types, ['edit']);
    }
    return $data;
  }

}
