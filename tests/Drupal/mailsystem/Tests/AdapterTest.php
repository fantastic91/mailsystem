<?php
/**
 * @file
 * Mailsystem Adapter test.
 *
 * @ingroup mailsystem
 */

namespace Drupal\mailsystem\Tests;

use Drupal\mailsystem\Adapter;
use Drupal\mailsystem\Plugin\Mail\Test;
use Drupal\Tests\UnitTestCase;

/**
 * Test the adapter class from mailsystem which is used as the mail plugin.
 *
 * @group mailsystem
 */
class AdapterTest extends UnitTestCase {

  /**
   * The Adapter we need to test.
   *
   * @var \Drupal\mailsystem\Adapter
   */
  protected $adapter;

  /**
   * Sender plugin instance.
   *
   * @var \Drupal\mailsystem\Plugin\Mail\Test
   */
  protected $sender;

  /**
   * Formatter plugin instance.
   *
   * @var \Drupal\mailsystem\Plugin\Mail\Test
   */
  protected $formatter;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->formatter = new Test();
    $this->sender = new Test();
    $this->adapter = new Adapter($this->formatter, $this->sender);
  }

  /**
   * Returns an empty message array to test with.
   *
   * @return array
   *   Associative array which holds an empty message to test with.
   */
  protected function getEmptyMessage() {
    return array(
      'subject' => 'test',
      'message' => 'message',
      'headers' => array(),
    );
  }

  /**
   * Test the right call to the formatting.
   */
  public function testFormatting() {
    $message = $this->adapter->format($this->getEmptyMessage());

    $this->assertEquals(Test::TEST_SUBJECT, $message['subject'], 'Subject match');
    $this->assertEquals(Test::TEST_BODY, $message['body'], 'Body match');
    $this->assertEquals(array(Test::TEST_HEADER_NAME => Test::TEST_HEADER_VALUE), $message['headers'], 'Header match');
  }

  /**
   * test for successful and failed sending of a message through the Adapter.
   */
  public function testSending() {
    $message = $this->getEmptyMessage();

    $this->assertFalse($this->adapter->mail($message), 'Sending message failed as expected');

    $message['subject'] = Test::SEND_SUCCESS_SUBJECT;
    $this->assertTrue($this->adapter->mail($message), 'Sending message successful as expected');
  }
}
