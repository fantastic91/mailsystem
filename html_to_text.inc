<?php

/**
 * Transform an HTML string into plain text, preserving the structure of the
 * markup. Useful for preparing the body of a node to be sent by e-mail.
 *
 * The output will be suitable for use as 'format=flowed; delsp=yes' text
 * (RFC 3676) and can be passed directly to drupal_mail() for sending.
 *
 * We deliberately use variable_get('mail_line_endings', MAIL_LINE_ENDINGS)
 * rather than "\r\n".
 *
 * This function provides suitable alternatives for the following tags:
 *
 * <a> <address> <b> <blockquote> <br /> <dd> <dl> <dt> <em>
 * <h1> <h2> <h3> <h4> <h5> <h6> <hr /> <i> <li> <ol> <p> <pre> <strong> <ul>
 *
 * The following tags are also handled:
 *
 * <del> <div> <ins> <tr>: Rendered the same as a <p> tag.
 *
 * <td>: A space is inserted between adjacent table cells.
 *
 * @param $string
 *   The string to be transformed.
 * @param $allowed_tags
 *   (optional) If supplied, a list of tags that will be transformed. If
 *   omitted, all supported tags are transformed.
 *
 * @return
 *   The transformed string.
 *
 * @see drupal_mail()
 */
function drupal_html_to_text($string, $allowed_tags = NULL) {
  $eol = variable_get('mail_line_endings', MAIL_LINE_ENDINGS);
  // Cache list of supported tags.
  static $supported_tags;
  if (!isset($supported_tags)) {
    $supported_tags = array(
      'a', 'address', 'b', 'blockquote', 'br', 'dd', 'del', 'div', 'dl', 'dt',
      'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'ins', 'li', 'ol',
      'p', 'pre', 'strong', 'td', 'tr', 'ul',
    );
  }

  // Make sure only supported tags are kept.
  $allowed_tags = isset($allowed_tags) ? array_intersect($supported_tags, $allowed_tags) : $supported_tags;

  // Parse $string into a DOM tree.
  $dom = filter_dom_load($string);
  $notes = array();
  $text = _mailsystem_html_to_text($dom->documentElement, $allowed_tags, $notes);
  // Add footnotes;
  foreach ($notes as $url => $note) {
    $text .= $eol . '[' . $note . '] ' . $url;
  }
  return trim($text, $eol);
}

/**
 * Helper function for drupal_html_to_text
 *
 * Recursively converts $node to text, wrapping and indenting as necessary.
 *
 * @param $node
 *   The source DOMNode.
 * @param $allowed_tags
 *   A list of tags that will be transformed.
 * @param $notes
 *   The list of footnotes, an associative array of (url => reference number) items.
 * @param $parents
 *   The list of ancestor tags, from nearest to most distant.
 * @param $count
 *   The number to use for the next list item within an ordered list.
 */
function _mailsystem_html_to_text(DOMNode $node, array $allowed_tags, array &$notes, $parents = array(), &$count = NULL) {
  if (is_null($count)) {
    $count = 1;
  }
  $eol = variable_get('mail_line_endings', MAIL_LINE_ENDINGS);
  if ($node->nodeType === XML_TEXT_NODE) {
    // For text nodes, we just copy the text content.
    $text = $node->textContent;
    // Collapse whitespace except within pre tags.
    if (!in_array('pre', $parents)) {
      $text = preg_replace('/[[:space:]]+/', ' ', $text);
    }
    return $text;
  }
  // Non-text node.
  $tag = '';
  $text = '';
  $child_text = '';
  $child_count = 1;
  $prefix = '';
  $indent = '';
  $suffix = '';
  if (isset($node->tagName) && in_array($node->tagName, $allowed_tags)) {
    $tag = $node->tagName;
    switch ($tag) {
      // Turn links with valid hrefs into footnotes.
      case 'a':
        if ( !empty($node->attributes)
          && ($href = $node->attributes->getNamedItem('href'))
          && ($url = $href->nodeValue)
          && valid_url($url) ) {
          // Only add links that have not already been added.
          if (isset($notes[$url])) {
            $note = $notes[$url];
          }
          else {
            $note = count($notes) + 1;
            $notes[$url] = $note;
          }
          $suffix = ' [' . $note . ']';
        }
        break;

      // Generic block-level tags.
      case 'address':
      case 'del':
      case 'div':
      case 'ins':
      case 'p':
      case 'pre':
        $text = $eol;
        $suffix = $eol;
        break;

      // Forced line break.
      case 'br':
        $text = $eol;
        break;

      // Boldface by wrapping with "*" characters.
      case 'b':
      case 'strong':
        $prefix = '*';
        $suffix = '*';
        break;

      // Italicize by wrapping with "/" characters.
      case 'em':
      case 'i':
        $prefix = '/';
        $suffix = '/';
        break;

      // Blockquotes are indented by "> " at each level.
      case 'blockquote':
        $text = $eol;
        $indent = '> ';
        $suffix = $eol;
        break;

      // Dictionary definitions are indented by four spaces.
      case 'dd':
        $indent = '    ';
        $suffix = $eol;
        break;

      // Dictionary list.
      case 'dl':
        // Start on a newline except inside other lists.
        if (!in_array('li', $parents)) {
          $text = $eol;
        }

      // Dictionary term.
      case 'dt':
        $suffix = $eol;
        break;

      // Header level 1 is prefixed by eight "=" characters.
      case 'h1':
        $text = $eol;
        $indent = '======== ';
        $suffix = $eol;
        break;

      // Header level 2 is prefixed by six "-" characters.
      case 'h2':
        $text = $eol;
        $indent = '------ ';
        $suffix = $eol;
        break;

      // Header level 3 is prefixed by four "." characters and a space.
      case 'h3':
        $text = $eol;
        $indent = '.... ';
        $suffix = $eol;
        break;

      // Header level 4 is prefixed by three "." characters and a space.
      case 'h4':
        $text = $eol;
        $indent = '... ';
        $suffix = $eol;
        break;

      // Header level 5 is prefixed by two "." character and a space.
      case 'h5':
        $text = $eol;
        $indent = '.. ';
        $suffix = $eol;
        break;

      // Header level 6 is prefixed by one "." character and a space.
      case 'h6':
        $text = $eol;
        $indent = '. ';
        $suffix = $eol;
        break;

      // Horizontal rulers become a line of 78 "-" characters.
      case 'hr':
        $text = $eol . str_repeat('-', 78) . $eol;
        break;

      // List items are treated differently depending on the parent tag.
      case 'li':
        // Ordered list item.
        if (reset($parents) === 'ol') {
          // Check the value attribute.
          if ( !empty($node->attributes)
            && ($value = $node->attributes->getNamedItem('value'))) {
            $count = $value->nodeValue;
          }
          $indent = " $count) ";
          $count++;
        }
        // Unordered list item.
        else {
          $indent = ' * ';
        }
        $suffix = $eol;
        break;

      // Ordered lists.
      case 'ol':
        // Start on a newline except inside other lists.
        if (!in_array('li', $parents)) {
          $text = $eol;
        }
        // Check the start attribute.
        if ( !empty($node->attributes)
          && ($value = $node->attributes->getNamedItem('start')) ) {
          $child_count = $value->nodeValue;
        }
        break;

      // Start and end tables on a new line.
      case 'table':
        $text = $eol;
        $suffix = $eol;
        break;

      // Wrap table cells in space characters.
      case 'td':
        $suffix = ' ';
        break;

      // End each table row with a newline.
      case 'tr':
        $suffix = $eol;
        break;

      // Unordered lists.
      case 'ul':
        // Start on a newline except inside other lists.
        if (!in_array('li', $parents)) {
          $text = $eol;
        }
        break;

      default:
        break;
    }
    // Only add allowed tags to the $parents array.
    array_unshift($parents, $tag);
  }
  // Copy each child node to output.
  if ($node->hasChildNodes()) {
    foreach ($node->childNodes as $child) {
      $child_text .= _mailsystem_html_to_text($child, $allowed_tags, $notes, $parents, $child_count);
    }
  }
  // We only add prefix and suffix if the child nodes were non-empty.
  if ($child_text) {
    // Don't add a newline to an existing newline.
    if ($suffix === $eol && drupal_substr($child_text, - strlen($eol)) === $eol) {
      $suffix = '';
    }
    $child_text = $prefix . $child_text . $suffix;
    $child_text = drupal_wrap_mail($child_text, $indent);
    // We capitalize the contents of h1 and h2 tags.
    if ($tag === 'h1' || $tag === 'h2') {
      $child_text = drupal_strtoupper($child_text);
      // For h1 and h2 tags at the top level, pad each non-empty line with the
      // character used for indentation.
      if (count($parents) == 1) {
        $pad = drupal_substr($indent, 0, 1);
        $lines = explode($eol, $child_text);
        foreach ($lines as $i => $line) {
          if ($line) {
            $lines[$i] = trim(_mailsystem_html_to_text_pad($line . ' ', $pad), $eol);
          }
        }
        $child_text = implode($eol, $lines);
      }
    }
    $text .= $child_text;
  }
  return $text;
}

/**
 * Helper function for drupal_html_to_text().
 *
 * Pad the last line with the given character.
 */
function _mailsystem_html_to_text_pad($text, $pad, $prefix = '') {
  $eol = variable_get('mail_line_endings', MAIL_LINE_ENDINGS);
  // Remove last line break.
  $text = preg_replace('/\r?\n$/s', '', $text);
  // Calculate needed padding space and add it.
  if (($p = strrpos($text, $eol)) === FALSE) {
    $p = -1;
  }
  else {
    // Convert position from byte count to character count.
    $p = drupal_strlen(substr($text, 0, $p));
  }
  // subtracting the result of strrpos()
  $n = max(0, 78 - (drupal_strlen($text) - $p) - drupal_strlen($prefix));
  // Add prefix and padding, and restore linebreak.
  return $text . $prefix . str_repeat($pad, $n) . $eol;
}
