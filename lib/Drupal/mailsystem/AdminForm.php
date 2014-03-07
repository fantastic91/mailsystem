<?php
/**
* @file
* Contains \Drupal\mailsystem\AdminForm.
*/

/**
 * @todo What is the "theme" for?
 *       I not get the use of this from the old code.
 */

namespace Drupal\mailsystem;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;

class AdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mailsystem_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // @todo: Make this as a property? Annotated or is there already something like this?
    $config = $this->configFactory->get('mailsystem.settings');

    $arguments = array(
      '!interface' => url('https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Mail!MailInterface.php/interface/MailInterface/8'),
      '@interface' => '\Drupal\Core\Mail\MailInterface',
      '!format' => url('https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Mail!MailInterface.php/function/MailInterface%3A%3Aformat/8'),
      '@format' => 'format()',
      '!mail' => url('https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Mail!MailInterface.php/function/MailInterface%3A%3Amail/8'),
      '@mail' => 'mail()',
    );

    // Default mail system.
    $form['mailsystem'] = array(
      '#type' => 'fieldset',
      '#title' => t('Default Mail System'),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    );

    // Default formatter plugin.
    $form['mailsystem']['default_formatter'] = array(
      '#type' => 'select',
      '#title' => t('Select the default formatter plugin:'),
      '#description' => t('Select the standard Plugin for formatting an email before sending it. This Plugin implements <a href="!interface">@interface</a> and in special the <a href="!format">@format</a> function.', $arguments),
      '#options' => $this->getFormatterPlugins(),
      '#default_value' => $config->get('defaults.formatter'),
    );

    // Default sender plugin.
    $form['mailsystem']['default_sender'] = array(
      '#type' => 'select',
      '#title' => t('Select the default sender plugin:'),
      '#description' => t('Select the standard Plugin for sending an email after formatting it. This Plugin implements <a href="!interface">@interface</a> and in special the <a href="!mail">@mail</a> function.', $arguments),
      '#options' => $this->getSenderPlugins(),
      '#default_value' => $config->get('defaults.sender'),
    );

    // Default theme for formatting emails.
    //$form['mailsystem']['default_theme'] = array(
    //  '#type' => 'select',
    //  '#title' => t('Theme to render the emails:'),
    //  '#description' => t('Select the theme that will be used to render the emails. This can be either the current theme, the default theme, the domain theme or any active theme.'),
    //  '#options' => $this->getThemesList(),
    //  '#default_value' => $config->get('defaults.theme'),
    //);

    // Fieldset for custom module configuration.
    $form['custom'] = array(
      '#type' => 'fieldset',
      '#title' => t('Custom module configurations'),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    );

    // Configuration for a new module.
    $form['custom']['custom_module'] = array(
      '#type' => 'select',
      '#title' => t('Module:'),
      '#options' => $this->getModulesList(),
      '#default_value' => '',
    );
    $form['custom']['custom_module_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Key:'),
      '#description' => t('This is a special value which is used to distinguish between different types of emails sent out by a module.<br/>Currently there is no way to extract them automatically, so you have to check the code and the hook_mail() function calls.'),
      '#default_value' => '',
    );
    $form['custom']['custom_formatter'] = array(
      '#type' => 'select',
      '#title' => t('Formatter plugin:'),
      '#options' => $this->getFormatterPlugins(TRUE),
      '#default_value' => 'none',
    );
    $form['custom']['custom_sender'] = array(
      '#type' => 'select',
      '#title' => t('Sender plugin:'),
      '#options' => $this->getSenderPlugins(TRUE),
      '#default_value' => 'none',
    );
    //$form['custom']['custom_theme'] = array(
    //  '#type' => 'select',
    //  '#title' => t('Theme:'),
    //  '#options' => $this->getThemesList(),
    //  '#default_value' => $config->get('defaults.theme'),
    //);

    // Show and change all custom configurations.
    $form['custom']['modules'] = array(
      '#type' => 'fieldset',
      '#title' => t('Custom module configurations'),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    );

    $config_data = $this->configFactory->get('');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $plugin_manager = \Drupal::service('plugin.manager.mail');
    $config = $this->configFactory->get('mailsystem.settings');

    // Set the default mail formatter.
    if (isset($form_state['values']['mailsystem']['default_formatter'])) {
      $class = $form_state['values']['mailsystem']['default_formatter'];
      $plugin = $plugin_manager->getDefinition($class);
      if (isset($plugin)) {
        $config->set('defaults.formatter', $class);
      }
    }

    // Set the default mail sender.
    if (isset($form_state['values']['mailsystem']['default_sender'])) {
      $class = $form_state['values']['mailsystem']['default_sender'];
      $plugin = $plugin_manager->getDefinition($class);
      if (isset($plugin)) {
        $config->set('defaults.sender', $class);
      }
    }

    // Set the default theme.
    if (isset($form_state['values']['mailsystem']['default_theme'])) {
      $config->set('defaults.theme', $form_state['values']['mailsystem']['default_theme']);
    }

    // Create a new module configuration if a module is selected.
    if (isset($form_state['values']['custom']['custom_module']) && ($form_state['values']['custom']['custom_module'] != 'none')) {
      $module = $form_state['values']['custom']['custom_module'];
      $key = $form_state['values']['custom']['custom_module_key'];
      $formatter = $form_state['values']['custom']['custom_formatter'];
      $sender = $form_state['values']['custom']['custom_sender'];

      // Create at least two configuration entries:
      // One for the sending and one for the formatting.
      //
      // The configuration entries can be:
      //  * module.key.type -> Plugin for a special mail and send/format function
      //  * module.key      -> Global plugin for a special mail.
      //  * module.type     -> Global plugin for the send/format function
      //  * module          -> Global plugin for the module
      $config_key = $module;
      $config_key .= !empty($key) ? '.' . $key : '';

      if ($formatter != 'none') {
        $this->set($config_key . MailsystemManager::MAILSYSTEM_TYPE_FORMATTING, $formatter);
      }
      if ($sender != 'none') {
        $this->set($config_key . MailsystemManager::MAILSYSTEM_TYPE_SENDING, $sender);
      }
    }

    // Finally save the configuration.
    $config->save();
  }

  /**
   * Returns a list with all formatter plugins.
   *
   * The plugin even must implement \Drupal\Core\Mail\MailInterface or the
   * interface we provide for this: \Drupal\mailsystem\FormatterInterface
   *
   * @param bool $showSelect
   *   If TRUE, a "-- Select --" entry is added as the first entry.
   *
   * @return array
   *   Associative array with all formatter plugins:
   *   - name: label
   */
  protected function getFormatterPlugins($showSelect = FALSE) {
    $list = array();

    // Add the "select" as first entry with the default mailsystem id as key.
    if (filter_var($showSelect, FILTER_VALIDATE_BOOLEAN)) {
      $list['none'] = t('-- Select --');
    }

    // Append all MailPlugins.
    $plugin_manager = \Drupal::service('plugin.manager.mail');
    foreach ($plugin_manager->getDefinitions() as $v) {
      $list[$v['id']] = $v['label'];
    }
    return $list;
  }

  /**
   * Returns a list with all mail sender plugins.
   *
   * The plugin even must implement \Drupal\Core\Mail\MailInterface or the
   * interface we provide for this: \Drupal\mailsystem\SenderInterface
   *
   * @param bool $showSelect
   *   If TRUE, a "-- Select --" entry is added as the first entry.
   *
   * @return array
   *   Associative array with all mail sender plugins:
   *   - name: label
   */
  protected function getSenderPlugins($showSelect = FALSE) {
    $list = array();

    // Add the "select" as first entry with the default mailsystem id as key.
    if (filter_var($showSelect, FILTER_VALIDATE_BOOLEAN)) {
      $list['none'] = t('-- Select --');
    }

    // Append all MailPlugins.
    $plugin_manager = \Drupal::service('plugin.manager.mail');
    foreach ($plugin_manager->getDefinitions() as $v) {
      $list[$v['id']] = $v['label'];
    }
    return $list;
  }

  /**
   * Returns a list with all themes.
   *
   * @return array
   *   Associative array with all enabled themes:
   *   - name: label
   */
  protected function getThemesList() {
    $theme_options = array(
      'current' => t('Current'),
      'default' => t('Default')
    );
    if (\Drupal::moduleHandler()->moduleExists('domain_theme')) {
      $theme_options['domain'] = t('Domain Theme');
    }
    foreach (\Drupal::service('theme_handler')->listInfo() as $name => $theme) {
      if ($theme->status === 1) {
        $theme_options[$name] = $theme->info['name'];
      }
    }
    return $theme_options;
  }

  /**
   * Returns a list with all modules which sends emails.
   *
   * Currently this is evaluated by the hook_mail implementation.
   *
   * @return array
   *   Associative array with all modules which sends emails:
   *   - module: label
   */
  protected function getModulesList() {
    $list = array(
      'none' => t('-- Select --'),
    );
    foreach (\Drupal::moduleHandler()->getImplementations('mail') as $module) {
      $list[$module] = ucfirst($module);
    }
    return $list;
  }

}