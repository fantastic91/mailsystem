<?php
/**
* @file
* Contains \Drupal\mailsystem\AdminForm.
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
      '!interface_formatter' => '#',
      '@interface_formatter' => '\Drupal\mailsystem\FormatterInterface',
      '!interface_sender' => '#',
      '@interface_sender' => '\Drupal\mailsystem\SenderInterface',
      '!format' => url('https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Mail!MailInterface.php/function/MailInterface%3A%3Aformat/8'),
      '@format' => 'format()',
      '!mail' => url('https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Mail!MailInterface.php/function/MailInterface%3A%3Amail/8'),
      '@mail' => 'mail()',
      '!default_class' => url('http://api.drupal.org/api/drupal/modules--system--system.mail.inc/class/DefaultMailSystem/8'),
      '@default_class' => $config->get('defaults.mailsystem_name'),
      '%module' => 'module',
      '%key' => 'key',
    );

    // Default mail system.
    $form['mailsystem'] = array(
      '#type' => 'fieldset',
      '#title' => t('Default Mail System'),
      //'#description' => t('Drupal provides a default <a href="!interface"><code>@interface</code></a> class called <a href="!default_class"><code>@default_class</code></a>. Modules may provide additional classes. Each <a href="!interface"><code>@interface</code></a> class may be associated with one or more identifiers, composed of a %module and an optional %key. Each email being sent also has a %module and a %key. To decide which class to use, Drupal uses the following search order: <ol><li>The class associated with the %module and %key, if any.</li><li>The class associated with the %module, if any.</li><li>The site-wide default <a href="!interface"><code>@interface</code></a> class.</li></ol>', $arguments),
      '#collapsible' => FALSE,
      '#tree' => TRUE,
    );

    $form['mailsystem']['default_formatter'] = array(
      '#type' => 'select',
      '#title' => t('Select the default formatter plugin:'),
      '#description' => t('Select the standard Mailer-Plugin for formatting the email before sending it. This is an instance from <a href="!interface">@interface</a> or <a href="!interface_formatter">@interface_formatter</a>', $arguments),
      '#options' => $this->getFormatterPlugins(),
      '#default_value' => $config->get('defaults.formatter'),
    );
    $form['mailsystem']['default_sender'] = array(
      '#type' => 'select',
      '#title' => t('Select the default sender plugin:'),
      '#description' => t('Select the standard Mailer-Plugin for sending the email after formatting it. This is an instance from <a href="!interface">@interface</a> or <a href="!interface_sender">@interface_sender</a>', $arguments),
      '#options' => $this->getSenderPlugins(),
      '#default_value' => $config->get('defaults.sender'),
    );

    // Theme to render the emails.
    $form['mailsystem']['theme'] = array(
      '#type' => 'select',
      '#title' => t('Theme to render the emails'),
      '#description' => t('Select the theme that will be used to render the emails. This can be either the current theme, the default theme, the domain theme or any active theme.'),
      '#options' => $this->getThemesList(),
      '#default_value' => $config->get('defaults.theme'),
    );

    // OLD
    /*$descriptions = array();
    foreach (system_rebuild_module_data() as $item) {
      if ($item->status) {
        $descriptions[$item->name] = (
          empty($item->info['package'])
            ? '' : $item->info['package']
          ) . ' » ' . t('!module module', array('!module' => $item->info['name']));
      }
    }
    asort($descriptions);

    foreach (array_diff_key($this->getMailsystem(), $this->getDefaultMailsystem()) as $id => $class) {
      // Separate $id into $module and $key.
      $module = $id;
      while ($module && empty($descriptions[$module])) {
        // Remove a key from the end
        $module = implode('_', explode('_', $module, -1));
      }

      // If an array key of the $mail_system variable is neither "default-system"
      // nor begins with a module name, then it should be unset.
      if (empty($module)) {
        watchdog('mailsystem', "Removing bogus mail_system key %id.", array('%id' => $id), WATCHDOG_WARNING);
        //unset($mail_system[$id]);
        continue;
      }

      // Set $title to the human-readable module name.
      $title = preg_replace('/^.* » /', '', $descriptions[$module]);
      if ($key = substr($id, strlen($module) + 1)) {
        $title .= " ($key key)";
      }
      $title .= ' class';
      // @todo separate here between the .sender and the .formatter setting
      $form['mailsystem'][$id] = array(
        '#type' => 'select',
        '#title' => $title,
        '#options' => $this->getFormatterPlugins(),
        '#default_value' => $class,
      );
    }

    $form['class'] = array(
      '#type' => 'fieldset',
      '#title' => t('New Class'),
      '#description' => t('Create a new <a href="!interface"><code>@interface</code></a> that inherits its methods from other classes. The new class will be named after the other classes it uses.', $arguments),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    );

    $form['class']['formatter'] = array(
      '#type' => 'select',
      '#title' => t('Plugin to use for formatting the mail.'),
      '#options' => $this->getFormatterPlugins(TRUE),
    );
    $form['class']['sender'] = array(
      '#type' => 'select',
      '#title' => t('Plugin to use for sending the mail.'),
      '#options' => $this->getSenderPlugins(TRUE),
    );
    $form['identifier'] = array(
      '#type' => 'fieldset',
      '#title' => t('New Setting'),
      '#description' => t('Add a new %module and %key to the settings list.',
        array(
          '%module' => 'module',
          '%key' => 'key',
        )
      ),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    );

    array_unshift($descriptions, t('-- Select --'));
    $form['identifier']['module'] = array(
      '#type' => 'select',
      '#title' => t('Module'),
      '#options' => $descriptions,
    );
    $form['identifier']['key'] = array(
      '#type' => 'textfield',
      '#title' => t('Key'),
      '#size' => 80,
    );*/

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
    $plugin_manager = \Drupal::service('plugin.manager.mailsystem');
    $config = $this->configFactory->get('mailsystem.settings');

    // Save the default mail formatter.
    if (isset($form_state['values']['mailsystem']['default_formatter'])) {
      $class = $form_state['values']['mailsystem']['default_formatter'];
      $plugin = $plugin_manager->getDefinition($class);
      if (isset($plugin)) {
        $config->set('defaults.formatter', $class);
      }
    }

    // Save the default mail sender.
    if (isset($form_state['values']['mailsystem']['default_sender'])) {
      $class = $form_state['values']['mailsystem']['default_sender'];
      $plugin = $plugin_manager->getDefinition($class);
      if (isset($plugin)) {
        $config->set('defaults.sender', $class);
      }
    }

    // Save the theme.
    if (isset($form_state['values']['mailsystem']['theme'])) {
      $config->set('defaults.theme', $form_state['values']['mailsystem']['theme']);
    }

    $config->save();
  }

  /**
   * Returns the default mail system id and name as an associative array.
   *
   * @return array
   *   Array with the id from the mail system and the name as value.
   */
  /*protected function getDefaultMailsystem() {
    $config = $this->configFactory->get('mailsystem.settings');
    return array($config->get('mailsystem_id') => $config->get('mailsystem_name'));
  }


  protected function getMailsystem() {
    $config = $this->configFactory->get('mailsystem.settings');
    $classes = $this->getFormatterPlugins();
    if (isset($classes[$config->get('mailsystem')])) {
      return array(
        $config->get('mailsystem') => $classes[$config->get('mailsystem')]
      );
    }
    return $this->getDefaultMailsystem();
  }*/

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
    $config = $this->configFactory->get('mailsystem.settings');

    // Add the "select" as first entry with the default mailsystem id as key.
    if (filter_var($showSelect, FILTER_VALIDATE_BOOLEAN)) {
      $list[$config->get('mailsystem_id')] = t('-- Select --');
    }

    // Append all MailPlugins.
    $plugin_manager = \Drupal::service('plugin.manager.mailsystem');
    foreach ($plugin_manager->getDefinitions() as $v) {
      $interfaces = class_implements($v['class']);
      if (isset($interfaces['Drupal\Core\Mail\MailInterface']) || isset($interfaces['Drupal\mailsystem\FormatterInterface'])) {
        $list[$v['id']] = $v['label'];
      }
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
    $config = $this->configFactory->get('mailsystem.settings');

    // Add the "select" as first entry with the default mailsystem id as key.
    if (filter_var($showSelect, FILTER_VALIDATE_BOOLEAN)) {
      $list[$config->get('mailsystem_id')] = t('-- Select --');
    }

    // Append all MailPlugins.
    $plugin_manager = \Drupal::service('plugin.manager.mailsystem');
    foreach ($plugin_manager->getDefinitions() as $v) {
      $interfaces = class_implements($v['class']);
      if (isset($interfaces['Drupal\Core\Mail\MailInterface']) || isset($interfaces['Drupal\mailsystem\SenderInterface'])) {
        $list[$v['id']] = $v['label'];
      }
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
    foreach (list_themes() as $name => $theme) {
      if ($theme->status === 1) {
        $theme_options[$name] = $theme->info['name'];
      }
    }
    return $theme_options;
  }

}