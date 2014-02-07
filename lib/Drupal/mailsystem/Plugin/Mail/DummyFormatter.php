<?php
/**
* @file
* Contains \Drupal\mailsystem\Plugin\mailsystem\Dummy.
*/

namespace Drupal\mailsystem\Plugin\Mail;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Mail\MailInterface;

/**
 * Provides a 'Dummy' plugin to format emails.
 *
 * @Plugin(
 *   id = "mailsystem_dummyformatter",
 *   label = @Translation("Dummy Mailsystem formatter Plugin"),
 *   description = @Translation("Dummy Plugin to debug the email on formatting ,does not sending anything.")
 * )
 */
class DummyFormatter extends PluginBase implements MailInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // TODO: Implement format() method.
    \debug(array(
      'Subject' => $message['subject'],
      'Body' => $message['body'],
      'Headers' => $message['headers'],
    ), 'DummyFormatter: format()');
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    return FALSE;
  }
}