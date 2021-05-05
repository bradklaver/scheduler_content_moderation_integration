<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Functional;

/**
 * Test covering the UnPublishedStateConstraintValidator.
 *
 * @coversDefaultClass \Drupal\scheduler_content_moderation_integration\Plugin\Validation\Constraint\UnPublishStateConstraintValidator
 *
 * @group scheduler_content_moderation_integration
 */
class UnPublishedStateConstraintTest extends SchedulerContentModerationBrowserTestBase {

  /**
   * Test published to unpublished transition.
   *
   * Test valid scheduled publishing state to valid scheduled un-publish
   * state transitions.
   *
   * @covers ::validate
   *
   * @dataProvider dataEntityTypes()
   */
  public function testValidPublishStateToUnPublishStateTransition($entityTypeId, $bundle) {
    $this->drupalLogin($entityTypeId == 'media' ? $this->schedulerMediaUser : $this->schedulerUser);
    $entity = $this->createEntity($entityTypeId, $bundle, [
      'moderation_state' => 'draft',
      'publish_on' => strtotime('+2 days'),
      'unpublish_on' => strtotime('+3 days'),
      'publish_state' => 'published',
      'unpublish_state' => 'archived',
    ]);
    // Assert that the publish and unpublish states pass validation.
    $violations = $entity->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Test an invalid un-publish transition.
   *
   * Test an invalid un-publish transition from current moderation state of
   * draft to archived state.
   *
   * @cover ::validate
   *
   * @dataProvider dataEntityTypes()
   */
  public function testInvalidUnPublishStateTransition($entityTypeId, $bundle) {
    $this->drupalLogin($entityTypeId == 'media' ? $this->schedulerMediaUser : $this->schedulerUser);

    $entity = $this->createEntity($entityTypeId, $bundle, [
      'moderation_state' => 'draft',
      'unpublish_on' => strtotime('+3 days'),
      'unpublish_state' => 'archived',
    ]);
    // Assert that the change from draft to archived fails validation.
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $message = (count($violations) > 0) ? $violations->get(0)->getMessage() : 'No violation message found';
    $this->assertEquals('The scheduled un-publishing state of <em class="placeholder">archived</em> is not a valid transition from the current moderation state of <em class="placeholder">draft</em> for this content.', $message);
  }

  /**
   * Test invalid transition.
   *
   * Test invalid transition from scheduled publish to scheduled un-publish
   * state.
   *
   * @covers ::validate
   *
   * @dataProvider dataEntityTypes()
   */
  public function testInvalidPublishStateToUnPublishStateTransition($entityTypeId, $bundle) {
    // This test is not about permissions, therefore we can use the root user
    // id 1 which will have permission to use the new state created below.
    $this->drupalLogin($this->rootUser);

    $this->workflow->getTypePlugin()
      ->addState('published_2', 'Published 2')
      ->addTransition('published_2', 'Published 2', ['draft'], 'published_2');

    $config = $this->workflow->getTypePlugin()->getConfiguration();
    $config['states']['published_2']['published'] = TRUE;
    $config['states']['published_2']['default_revision'] = TRUE;
    $this->workflow->getTypePlugin()->setConfiguration($config);
    $this->workflow->save();

    $entity = $this->createEntity($entityTypeId, $bundle, [
      'moderation_state' => 'draft',
      'publish_on' => strtotime('+1 day'),
      'publish_state' => 'published_2',
      'unpublish_on' => strtotime('+2 days'),
      'unpublish_state' => 'archived',
    ]);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $message = (count($violations) > 0) ? $violations->get(0)->getMessage() : 'No violation message found';
    $this->assertEquals('The scheduled un-publishing state of <em class="placeholder">archived</em> is not a valid transition from the scheduled publishing state of <em class="placeholder">published_2</em>.', $message);
  }

}
