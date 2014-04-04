<?php
/**
* @file
* Contains \Drupal\mailsystem\Plugin\Mail\Test.
*/

namespace Drupal\mailsystem\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;

/**
 * Provides a 'Dummy' plugin to send emails.
 *
 * @Mail(
 *   id = "mailsystem_test",
 *   label = @Translation("Test Mail-Plugin"),
 *   description = @Translation("Test Plugin to use in PHP Unit Tests.")
 * )
 */
class Test implements MailInterface {
  const TEST_SUBJECT = 'Subject';
  const TEST_BODY = 'Vivamus varius commodo leo at eleifend. Nunc vestibulum dolor eget turpis pulvinar volutpat.';
  const TEST_HEADER_NAME = 'X-System';
  const TEST_HEADER_VALUE = 'D8 PHP Unit test';
  const SEND_SUCCESS_SUBJECT = 'Failed';

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    return array(
      'subject' => self::TEST_SUBJECT,
      'body' => self::TEST_BODY,
      'headers' => array(self::TEST_HEADER_NAME => self::TEST_HEADER_VALUE),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    return ($message['subject'] == self::SEND_SUCCESS_SUBJECT);
  }
}